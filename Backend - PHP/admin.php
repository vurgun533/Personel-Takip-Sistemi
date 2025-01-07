<?php
require_once 'config.php';

// Personelleri gruplandırarak son giriş kayıtlarıyla birlikte getir
$sql = "SELECT 
            p.PersonelId,
            p.PersonelAdi,
            MAX(p.Tarih) as SonIslem,
            (SELECT GirisTipi 
             FROM GirisKayitlari g2 
             WHERE g2.PersonelId = p.PersonelId 
             ORDER BY g2.Tarih DESC LIMIT 1) as SonDurum,
            (SELECT KapiAdi 
             FROM GirisKayitlari g3 
             WHERE g3.PersonelId = p.PersonelId 
             ORDER BY g3.Tarih DESC LIMIT 1) as SonKapi
        FROM GirisKayitlari p
        GROUP BY p.PersonelId, p.PersonelAdi
        ORDER BY p.PersonelAdi";

try {
    $result = $conn->query($sql);
} catch(PDOException $e) {
    die("Sorgu hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Giriş Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-in {
            background-color: #28a745;
        }
        .status-out {
            background-color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <header class="bg-white shadow-sm mb-4">
        <div class="container d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0">Personel Takip Sistemi</h1>
            <a href="index.html" class="btn btn-outline-success d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-qr-code-scan" viewBox="0 0 16 16">
                    <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3Zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5ZM.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5Zm15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5ZM4 4h1v1H4V4Z"/>
                    <path d="M7 2H2v5h5V2ZM3 3h3v3H3V3Zm2 8H4v1h1v-1Z"/>
                    <path d="M7 9H2v5h5V9Zm-4 1h3v3H3v-3Zm8-6h1v1h-1V4Z"/>
                    <path d="M9 2h5v5H9V2Zm1 1v3h3V3h-3ZM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8H8Zm2 2H9V9h1v1Zm4 2h-1v1h-2v1h3v-2Zm-4 2v-1H8v1h2Z"/>
                    <path d="M12 9h2V8h-2v1Z"/>
                </svg>
                QR Kod Sistemi
            </a>
        </div>
    </header>

    <div class="container py-5">
     
        <div class="row">
            <?php while ($row = $result->fetch()) { ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <span class="status-indicator <?php echo $row['SonDurum'] == 'Giriş' ? 'status-in' : 'status-out'; ?>"></span>
                                    <?php echo $row['PersonelAdi']; ?>
                                </h5>
                                <span class="badge bg-secondary">#<?php echo $row['PersonelId']; ?></span>
                            </div>
                            <div class="card-text">
                                <small class="text-muted">Son İşlem:</small>
                                <p class="mb-2">
                                    <span class="badge <?php echo $row['SonDurum'] == 'Giriş' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $row['SonDurum']; ?>
                                    </span>
                                    <?php echo date('d.m.Y H:i', strtotime($row['SonIslem'])); ?>
                                </p>
                                <small class="text-muted">Son Konum:</small>
                                <p class="mb-0"><?php echo $row['SonKapi']; ?></p>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="personelDetay(<?php echo $row['PersonelId']; ?>, '<?php echo $row['PersonelAdi']; ?>')">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Geçmiş
                                </button>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-graph-up me-1"></i>
                                    İstatistik
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Yeni Kayıt Modal -->
    <div class="modal fade" id="yeniKayitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Giriş Kaydı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="kaydet.php" method="POST">
                    <div class="modal-body">
                        <!-- Personel Seçimi -->
                        <div class="mb-3">
                            <label class="form-label">Personel</label>
                            <select class="form-select" name="personelId" id="personelSelect" required>
                                <option value="">Personel Seçiniz</option>
                                <option value="1" data-ad="Ahmet">Ahmet</option>
                                <option value="2" data-ad="Murat">Murat</option>
                                <option value="3" data-ad="Hüseyin">Hüseyin</option>
                                <option value="4" data-ad="Kamil">Kamil</option>
                                <option value="5" data-ad="Zeki">Zeki</option>
                            </select>
                            <input type="hidden" name="personelAdi" id="personelAdi">
                        </div>

                        <!-- Kapı Seçimi -->
                        <div class="mb-3">
                            <label class="form-label">Kapı</label>
                            <select class="form-select" name="kapiId" id="kapiSelect" required>
                                <option value="">Kapı Seçiniz</option>
                                <option value="1" data-ad="Ana Kapı">Ana Kapı</option>
                                <option value="2" data-ad="Personel Girişi">Personel Girişi</option>
                                <option value="3" data-ad="Araç Girişi">Araç Girişi</option>
                                <option value="4" data-ad="Servis Girişi">Servis Girişi</option>
                                <option value="5" data-ad="Acil Durum Kapısı">Acil Durum Kapısı</option>
                                <option value="6" data-ad="Teras Girişi">Teras Girişi</option>
                            </select>
                            <input type="hidden" name="kapiAdi" id="kapiAdi">
                        </div>

                        <!-- Son İşlem Bilgisi -->
                        <div class="alert alert-info" id="sonIslemBilgisi" style="display: none;">
                            <small>Son İşlem: <span id="sonIslemText">-</span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Geçmiş Modal -->
    <div class="modal fade" id="gecmisModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Personel Geçmiş Kayıtları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="date" class="form-control" id="gecmisTarih" onchange="gecmisGetir()">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="gecmisTable">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>İşlem</th>
                                    <th>Kapı</th>
                                </tr>
                            </thead>
                            <tbody id="gecmisBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Modal -->
    <div class="modal fade" id="istatistikModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Personel İstatistikleri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Özet Kartlar -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>Aylık Ortalama Çalışma</h6>
                                    <h3 id="aylikOrtalama">-- saat</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Bu Ay Toplam Giriş</h6>
                                    <h3 id="aylikGiris">--</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>Ortalama Giriş Saati</h6>
                                    <h3 id="ortalamaGiris">--</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6>En Sık Kullanılan Kapı</h6>
                                    <h3 id="enSikKapi">--</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Devamsızlık ve İzin Bilgileri -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Aylık İzin Durumu</h5>
                                    <span class="badge bg-secondary" id="toplamIzinGun">Toplam İzin: -- gün</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Ay</th>
                                                    <th>Toplam İş Günü</th>
                                                    <th>İzinli Günler</th>
                                                    <th>Devam Oranı</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </thead>
                                            <tbody id="devamsizlikTablo"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Özet Tablo -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Son 30 Günlük Özet</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Giriş</th>
                                                    <th>Çıkış</th>
                                                    <th>Toplam Süre</th>
                                                    <th>Kullanılan Kapılar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ozetTablo"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    let calismaGrafik;
    let kapiGrafik;

    function personelDetay(personelId, personelAdi) {
        document.querySelector('#gecmisModal .modal-title').textContent = 
            personelAdi + ' - Geçmiş Kayıtlar';
        
        gecmisGetir(personelId);
        const modal = new bootstrap.Modal(document.getElementById('gecmisModal'));
        modal.show();
    }

    function istatistikGoster(personelId, personelAdi) {
        document.querySelector('#istatistikModal .modal-title').textContent = 
            personelAdi + ' - İstatistikler';
        
        fetch(`getIstatistik.php?personelId=${personelId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Veri getirme hatası: ' + data.error);
                    return;
                }

                // İstatistik kartlarını güncelle
                document.getElementById('aylikOrtalama').textContent = data.aylikOrtalama || '0 saat';
                document.getElementById('enSikKapi').textContent = data.enSikKapi || 'Veri yok';
                document.getElementById('aylikGiris').textContent = data.aylikGiris || '0';
                document.getElementById('ortalamaGiris').textContent = data.ortalamaGiris || '--:--';

                // Devamsızlık tablosunu güncelle
                if (data.devamsizlik && data.devamsizlik.length > 0) {
                    let toplamIzin = 0;
                    document.getElementById('devamsizlikTablo').innerHTML = data.devamsizlik.map(ay => {
                        toplamIzin += parseInt(ay.gelmemeSayisi);
                        return `
                            <tr>
                                <td>${ay.ay}</td>
                                <td>${ay.toplamGun} gün</td>
                                <td>
                                    <span class="badge ${ay.gelmemeSayisi > 3 ? 'bg-danger' : 'bg-warning'}">
                                        ${ay.gelmemeSayisi} gün
                                    </span>
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar ${ay.devamOrani >= 90 ? 'bg-success' : 'bg-warning'}" 
                                             role="progressbar" 
                                             style="width: ${ay.devamOrani}%">
                                            %${ay.devamOrani}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    ${ay.devamOrani >= 90 
                                        ? '<span class="badge bg-success">İyi</span>' 
                                        : ay.devamOrani >= 75 
                                            ? '<span class="badge bg-warning">Orta</span>'
                                            : '<span class="badge bg-danger">Düşük</span>'
                                    }
                                </td>
                            </tr>
                        `;
                    }).join('');
                    
                    document.getElementById('toplamIzinGun').textContent = `Toplam İzin: ${toplamIzin} gün`;
                }

                // Özet tablosunu güncelle
                if (data.ozet && data.ozet.length > 0) {
                    document.getElementById('ozetTablo').innerHTML = data.ozet.map(gun => `
                        <tr>
                            <td>${gun.tarih}</td>
                            <td>${gun.giris || '--:--'}</td>
                            <td>${gun.cikis || '--:--'}</td>
                            <td>${gun.sure || '0 saat'}</td>
                            <td>${gun.kapilar || '-'}</td>
                        </tr>
                    `).join('');
                } else {
                    document.getElementById('ozetTablo').innerHTML = 
                        '<tr><td colspan="5" class="text-center">Kayıt bulunamadı</td></tr>';
                }
            })
            .catch(error => {
                console.error('Veri getirme hatası:', error);
                alert('Veriler getirilirken bir hata oluştu.');
            });

        const modal = new bootstrap.Modal(document.getElementById('istatistikModal'));
        modal.show();
    }

    function gecmisGetir(personelId, tarih = null) {
        const params = new URLSearchParams({
            personelId: personelId,
            tarih: tarih || document.getElementById('gecmisTarih').value || new Date().toISOString().split('T')[0]
        });

        fetch(`getGecmis.php?${params}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('gecmisBody');
                if (data && data.length > 0) {
                    tbody.innerHTML = data.map(kayit => `
                        <tr>
                            <td>${kayit.tarih}</td>
                            <td>${kayit.saat}</td>
                            <td>
                                <span class="badge ${kayit.islem === 'Giriş' ? 'bg-success' : 'bg-danger'}">
                                    ${kayit.islem}
                                </span>
                            </td>
                            <td>${kayit.kapi}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Kayıt bulunamadı</td></tr>';
                }
            })
            .catch(error => {
                console.error('Geçmiş kayıtlar getirilemedi:', error);
                document.getElementById('gecmisBody').innerHTML = 
                    '<tr><td colspan="4" class="text-center text-danger">Veriler yüklenirken hata oluştu</td></tr>';
            });
    }

    // Kart butonlarına click event listener ekleyin
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-outline-info').forEach(btn => {
            btn.onclick = function() {
                const personelId = this.closest('.card').querySelector('.badge').textContent.replace('#', '');
                const personelAdi = this.closest('.card').querySelector('.card-title').textContent.trim();
                personelDetay(personelId, personelAdi);
            };
        });
        
        document.querySelectorAll('.btn-outline-primary').forEach(btn => {
            btn.onclick = function() {
                const personelId = this.closest('.card').querySelector('.badge').textContent.replace('#', '');
                const personelAdi = this.closest('.card').querySelector('.card-title').textContent.trim();
                istatistikGoster(personelId, personelAdi);
            };
        });
    });

    // Personel seçildiğinde
    document.getElementById('personelSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const personelAdi = selectedOption.getAttribute('data-ad');
        document.getElementById('personelAdi').value = personelAdi;
        
        if (this.value) {
            // Son işlem bilgisini getir
            fetch(`getSonIslem.php?personelId=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    const sonIslemDiv = document.getElementById('sonIslemBilgisi');
                    const sonIslemText = document.getElementById('sonIslemText');
                    
                    if (data.sonIslem) {
                        sonIslemText.textContent = `${data.sonIslem.tarih} - ${data.sonIslem.girisTipi}`;
                        sonIslemDiv.style.display = 'block';
                    } else {
                        sonIslemDiv.style.display = 'none';
                    }
                });
        }
    });

    // Kapı seçildiğinde
    document.getElementById('kapiSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const kapiAdi = selectedOption.getAttribute('data-ad');
        document.getElementById('kapiAdi').value = kapiAdi;
    });
    </script>
</body>
</html> 