<?php
require_once 'config.php';

$personeller = [
    ['id' => 1, 'ad' => 'Ahmet', 'izinGunleri' => 0],
    ['id' => 2, 'ad' => 'Murat', 'izinGunleri' => 25],
    ['id' => 3, 'ad' => 'Hüseyin', 'izinGunleri' => 15],
    ['id' => 4, 'ad' => 'Kamil', 'izinGunleri' => 10],
    ['id' => 5, 'ad' => 'Zeki', 'izinGunleri' => 7]
];

$kapilar = [
    ['id' => '1', 'ad' => 'Ana Kapı'],
    ['id' => '2', 'ad' => 'Personel Girişi'],
    ['id' => '3', 'ad' => 'Araç Girişi'],
    ['id' => '4', 'ad' => 'Servis Girişi'],
    ['id' => '5', 'ad' => 'Acil Durum Kapısı'],
    ['id' => '6', 'ad' => 'Teras Girişi']
];

try {
    $conn->exec("TRUNCATE TABLE GirisKayitlari");
    
    $sql = "INSERT INTO GirisKayitlari (PersonelId, PersonelAdi, KapiId, KapiAdi, Tarih, GirisTipi) 
            VALUES (:personelId, :personelAdi, :kapiId, :kapiAdi, :tarih, :girisTipi)";
    $stmt = $conn->prepare($sql);
    
    // Tarih aralığını belirle
    $startDate = strtotime('2024-08-01');  // 1 Ağustos 2024
    $endDate = strtotime('2025-01-06');    // 6 Ocak 2025
    
    foreach ($personeller as $personel) {
        // İzin günlerini rastgele seç
        $izinGunleri = [];
        $kalanIzin = $personel['izinGunleri'];
        
        if ($kalanIzin > 0) {
            while ($kalanIzin > 0) {
                $randomDay = rand($startDate, $endDate);
                $randomDay = strtotime(date('Y-m-d', $randomDay)); // Günün başlangıcına ayarla
                
                // Hafta sonu değilse ve daha önce seçilmemişse
                if (date('N', $randomDay) < 6 && !in_array($randomDay, $izinGunleri)) {
                    $izinGunleri[] = $randomDay;
                    $kalanIzin--;
                }
            }
        }
        
        // Her gün için kayıt oluştur
        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $currentDate = date('Y-m-d', $date);
            $dayOfWeek = date('N', $date);
            
            // Hafta sonu veya izin günü ise atla
            if ($dayOfWeek >= 6 || in_array(strtotime($currentDate), $izinGunleri)) {
                continue;
            }
            
            // Giriş saati: 08:00 - 09:30 arası
            $girisHour = rand(8, 9);
            $girisMinute = $girisHour == 9 ? rand(0, 30) : rand(0, 59);
            $girisTarihi = $currentDate . ' ' . sprintf('%02d:%02d:00', $girisHour, $girisMinute);
            
            // Çalışma süresi: 6-10 saat
            $calismaSuresi = rand(6 * 3600, 10 * 3600);
            $cikisTarihi = date('Y-m-d H:i:s', strtotime($girisTarihi) + $calismaSuresi);
            
            // Giriş kaydı
            $girisKapi = $kapilar[array_rand($kapilar)];
            $stmt->execute([
                ':personelId' => $personel['id'],
                ':personelAdi' => $personel['ad'],
                ':kapiId' => $girisKapi['id'],
                ':kapiAdi' => $girisKapi['ad'],
                ':tarih' => $girisTarihi,
                ':girisTipi' => 'Giriş'
            ]);
            
            // Çıkış kaydı
            $cikisKapi = $kapilar[array_rand($kapilar)];
            $stmt->execute([
                ':personelId' => $personel['id'],
                ':personelAdi' => $personel['ad'],
                ':kapiId' => $cikisKapi['id'],
                ':kapiAdi' => $cikisKapi['ad'],
                ':tarih' => $cikisTarihi,
                ':girisTipi' => 'Çıkış'
            ]);
        }
    }
    
    // Oluşturulan kayıt sayısını hesapla
    $sql = "SELECT COUNT(*) as toplam FROM GirisKayitlari";
    $stmt = $conn->query($sql);
    $toplamKayit = $stmt->fetch()['toplam'];
    
    echo "Veriler başarıyla oluşturuldu!\n";
    echo "Toplam " . $toplamKayit . " kayıt oluşturuldu.\n";
    echo "Tarih Aralığı: 1 Ağustos 2024 - 6 Ocak 2025\n";
    echo "Hafta sonları hariç, her personel için günlük giriş-çıkış kayıtları oluşturuldu.\n";
    echo "Her personel için belirtilen sayıda izin günü rastgele dağıtıldı.\n";
    
} catch(PDOException $e) {
    die("Veri oluşturma hatası: " . $e->getMessage());
}
?> 