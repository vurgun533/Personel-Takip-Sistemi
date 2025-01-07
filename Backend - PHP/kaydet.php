<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // JSON verisini al
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        // JSON verisi geçerli mi kontrol et
        if (!$data) {
            throw new Exception('Geçersiz JSON verisi');
        }

        // Gerekli alanları kontrol et
        $requiredFields = ['personelId', 'personelAdi', 'kapiId', 'kapiAdi'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Eksik alan: $field");
            }
        }

        $personelId = $data['personelId'];
        $personelAdi = $data['personelAdi'];
        $kapiId = $data['kapiId'];
        $kapiAdi = $data['kapiAdi'];
        $tarih = date('Y-m-d H:i:s');

        // Son işlemi kontrol et
        $sql = "SELECT GirisTipi 
                FROM GirisKayitlari 
                WHERE PersonelId = :personelId 
                ORDER BY Tarih DESC 
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':personelId' => $personelId]);
        $sonKayit = $stmt->fetch();

        // İşlem tipini belirle
        if (!$sonKayit || $sonKayit['GirisTipi'] === 'Giriş') {
            $girisTipi = 'Çıkış';
        } else {
            $girisTipi = 'Giriş';
        }

        // Yeni kaydı ekle
        $sql = "INSERT INTO GirisKayitlari (PersonelId, PersonelAdi, KapiId, KapiAdi, Tarih, GirisTipi) 
                VALUES (:personelId, :personelAdi, :kapiId, :kapiAdi, :tarih, :girisTipi)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':personelId' => $personelId,
            ':personelAdi' => $personelAdi,
            ':kapiId' => $kapiId,
            ':kapiAdi' => $kapiAdi,
            ':tarih' => $tarih,
            ':girisTipi' => $girisTipi
        ]);

        // Başarılı yanıt döndür
        $response = [
            'success' => true,
            'message' => 'Kayıt başarıyla eklendi',
            'data' => [
                'personelId' => $personelId,
                'personelAdi' => $personelAdi,
                'kapiAdi' => $kapiAdi,
                'tarih' => $tarih,
                'girisTipi' => $girisTipi
            ]
        ];
        
        echo json_encode($response);
        
    } catch(Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ];
        
        http_response_code(400);
        echo json_encode($response);
    }
}
?> 