<?php
require_once 'config.php';

header('Content-Type: application/json');

$personelId = $_GET['personelId'] ?? null;

if (!$personelId) {
    die(json_encode(['error' => 'Personel ID gerekli']));
}

try {
    $sql = "SELECT 
                DATE(Tarih) as tarih,
                TIME_FORMAT(TIME(Tarih), '%H:%i') as saat,
                GirisTipi as islem,
                KapiAdi as kapi
            FROM GirisKayitlari 
            WHERE PersonelId = :personelId 
            ORDER BY Tarih DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':personelId' => $personelId
    ]);
    
    $kayitlar = $stmt->fetchAll();
    
    // Tarihleri formatla
    $kayitlar = array_map(function($kayit) {
        $kayit['tarih'] = date('d.m.Y', strtotime($kayit['tarih']));
        return $kayit;
    }, $kayitlar);
    
    echo json_encode($kayitlar);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 