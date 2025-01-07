<?php
require_once 'config.php';

header('Content-Type: application/json');

$personelId = $_GET['personelId'] ?? null;

if (!$personelId) {
    die(json_encode(['error' => 'Personel ID gerekli']));
}

try {
    $sql = "SELECT 
                DATE_FORMAT(Tarih, '%d.%m.%Y %H:%i') as tarih,
                GirisTipi
            FROM GirisKayitlari 
            WHERE PersonelId = :personelId 
            ORDER BY Tarih DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':personelId' => $personelId]);
    $sonIslem = $stmt->fetch();
    
    echo json_encode(['sonIslem' => $sonIslem]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 