<?php
require_once 'config.php';

header('Content-Type: application/json');

$personelId = $_GET['personelId'] ?? null;

if (!$personelId) {
    die(json_encode(['error' => 'Personel ID gerekli']));
}

try {
    // 1. Aylık ortalama çalışma süresi
    $sql = "SELECT 
                AVG(TIME_TO_SEC(TIMEDIFF(cikis.Tarih, giris.Tarih)) / 3600) as ortalama_saat
            FROM 
                (SELECT Tarih FROM GirisKayitlari 
                 WHERE PersonelId = :personelId AND GirisTipi = 'Giriş' 
                 AND MONTH(Tarih) = MONTH(CURRENT_DATE())) giris
            JOIN 
                (SELECT Tarih FROM GirisKayitlari 
                 WHERE PersonelId = :personelId AND GirisTipi = 'Çıkış' 
                 AND MONTH(Tarih) = MONTH(CURRENT_DATE())) cikis
            ON DATE(giris.Tarih) = DATE(cikis.Tarih)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $aylikOrtalama = $stmt->fetch()['ortalama_saat'] ?? 0;

    // 2. En sık kullanılan kapı ve kullanım sayısı
    $sql = "SELECT 
                KapiAdi, 
                COUNT(*) as kullanim_sayisi
            FROM GirisKayitlari 
            WHERE PersonelId = :personelId
            GROUP BY KapiAdi
            ORDER BY kullanim_sayisi DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $kapiKullanim = $stmt->fetchAll();
    $enSikKapi = $kapiKullanim[0]['KapiAdi'] ?? '';

    // 3. Bu ayki toplam giriş sayısı
    $sql = "SELECT COUNT(*) as toplam_giris
            FROM GirisKayitlari 
            WHERE PersonelId = :personelId 
            AND GirisTipi = 'Giriş'
            AND MONTH(Tarih) = MONTH(CURRENT_DATE())
            AND YEAR(Tarih) = YEAR(CURRENT_DATE())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $aylikGiris = $stmt->fetch()['toplam_giris'];

    // 4. Ortalama giriş saati
    $sql = "SELECT 
                TIME_FORMAT(TIME(AVG(UNIX_TIMESTAMP(Tarih))), '%H:%i') as ortalama_giris
            FROM GirisKayitlari 
            WHERE PersonelId = :personelId 
            AND GirisTipi = 'Giriş'
            AND MONTH(Tarih) = MONTH(CURRENT_DATE())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $ortalamaGiris = $stmt->fetch()['ortalama_giris'];

    // 5. Son 7 günlük çalışma saatleri
    $sql = "SELECT 
                DATE(g1.Tarih) as tarih,
                TIME_FORMAT(MIN(CASE WHEN g1.GirisTipi = 'Giriş' THEN g1.Tarih END), '%H:%i') as giris_saat,
                TIME_FORMAT(MAX(CASE WHEN g1.GirisTipi = 'Çıkış' THEN g1.Tarih END), '%H:%i') as cikis_saat,
                ROUND(
                    TIME_TO_SEC(
                        TIMEDIFF(
                            MAX(CASE WHEN g1.GirisTipi = 'Çıkış' THEN g1.Tarih END),
                            MIN(CASE WHEN g1.GirisTipi = 'Giriş' THEN g1.Tarih END)
                        )
                    ) / 3600, 1
                ) as calisma_saat
            FROM GirisKayitlari g1
            WHERE g1.PersonelId = :personelId
            AND g1.Tarih >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(g1.Tarih)
            ORDER BY g1.Tarih DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $haftalikData = $stmt->fetchAll();

    // 6. Son 30 günlük detaylı özet
    $sql = "SELECT 
                DATE(g.Tarih) as tarih,
                TIME_FORMAT(MIN(CASE WHEN g.GirisTipi = 'Giriş' THEN g.Tarih END), '%H:%i') as ilk_giris,
                TIME_FORMAT(MAX(CASE WHEN g.GirisTipi = 'Çıkış' THEN g.Tarih END), '%H:%i') as son_cikis,
                ROUND(
                    TIME_TO_SEC(
                        TIMEDIFF(
                            MAX(CASE WHEN g.GirisTipi = 'Çıkış' THEN g.Tarih END),
                            MIN(CASE WHEN g.GirisTipi = 'Giriş' THEN g.Tarih END)
                        )
                    ) / 3600, 1
                ) as toplam_sure,
                GROUP_CONCAT(DISTINCT g.KapiAdi ORDER BY g.Tarih) as kullanilan_kapilar
            FROM GirisKayitlari g
            WHERE g.PersonelId = :personelId
            AND g.Tarih >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY DATE(g.Tarih)
            ORDER BY tarih DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $aylikOzet = $stmt->fetchAll();

    // Kapı kullanım dağılımı için veri hazırlama
    $kapilar = array_column($kapiKullanim, 'KapiAdi');
    $kapiKullanimSayilari = array_column($kapiKullanim, 'kullanim_sayisi');

    // Türkçe ay isimleri için yardımcı fonksiyon
    function getTurkceAy($date) {
        $aylar = [
            'January' => 'Ocak',
            'February' => 'Şubat',
            'March' => 'Mart',
            'April' => 'Nisan',
            'May' => 'Mayıs',
            'June' => 'Haziran',
            'July' => 'Temmuz',
            'August' => 'Ağustos',
            'September' => 'Eylül',
            'October' => 'Ekim',
            'November' => 'Kasım',
            'December' => 'Aralık'
        ];
        
        $ayIngilizce = date('F', strtotime($date));
        $yil = date('Y', strtotime($date));
        
        return $aylar[$ayIngilizce] . ' ' . $yil;
    }

    // 7. Giriş yapılmayan günlerin istatistiği
    $sql = "WITH RECURSIVE tarihler AS (
                SELECT CURDATE() - INTERVAL 90 DAY as tarih
                UNION ALL
                SELECT tarih + INTERVAL 1 DAY
                FROM tarihler
                WHERE tarih < CURDATE()
            ),
            calisma_gunleri AS (
                SELECT tarih 
                FROM tarihler 
                WHERE DAYOFWEEK(tarih) NOT IN (1, 7) -- Hafta sonlarını hariç tut
            ),
            giris_gunleri AS (
                SELECT DISTINCT DATE(Tarih) as giris_tarih
                FROM GirisKayitlari
                WHERE PersonelId = :personelId
                AND GirisTipi = 'Giriş'
            )
            SELECT 
                YEAR(cg.tarih) as yil,
                MONTH(cg.tarih) as ay,
                COUNT(*) as toplam_is_gunu,
                SUM(CASE WHEN gg.giris_tarih IS NULL THEN 1 ELSE 0 END) as gelmeme_sayisi
            FROM calisma_gunleri cg
            LEFT JOIN giris_gunleri gg ON cg.tarih = gg.giris_tarih
            GROUP BY YEAR(cg.tarih), MONTH(cg.tarih)
            ORDER BY yil DESC, ay DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $devamsizlik = $stmt->fetchAll();

    
    // JSON çıktısına devamsızlık verilerini ekle
    $jsonData = [
        'aylikOrtalama' => number_format($aylikOrtalama, 1) . ' saat',
        'enSikKapi' => $enSikKapi,
        'aylikGiris' => $aylikGiris,
        'ortalamaGiris' => $ortalamaGiris,
        'gunler' => array_map(fn($row) => date('d.m', strtotime($row['tarih'])), $haftalikData),
        'saatler' => array_map(fn($row) => $row['calisma_saat'], $haftalikData),
        'kapilar' => $kapilar,
        'kapiKullanim' => $kapiKullanimSayilari,
        'devamsizlik' => array_map(function($row) {
            return [
                'ay' => getTurkceAy($row['yil'].'-'.$row['ay'].'-01'),
                'toplamGun' => $row['toplam_is_gunu'],
                'gelmemeSayisi' => $row['gelmeme_sayisi'],
                'devamOrani' => round(
                    (($row['toplam_is_gunu'] - $row['gelmeme_sayisi']) / $row['toplam_is_gunu']) * 100, 1
                )
            ];
        }, $devamsizlik),
        'ozet' => array_map(function($row) {
            return [
                'tarih' => date('d.m.Y', strtotime($row['tarih'])),
                'giris' => $row['ilk_giris'],
                'cikis' => $row['son_cikis'],
                'sure' => $row['toplam_sure'] . ' saat',
                'kapilar' => $row['kullanilan_kapilar']
            ];
        }, $aylikOzet)
    ];

    echo json_encode($jsonData);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 