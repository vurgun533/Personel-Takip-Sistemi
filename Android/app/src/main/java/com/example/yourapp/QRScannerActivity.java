package com.example.yourapp;

import android.Manifest;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.util.Log;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import com.journeyapps.barcodescanner.BarcodeCallback;
import com.journeyapps.barcodescanner.BarcodeResult;
import com.journeyapps.barcodescanner.DecoratedBarcodeView;
import org.json.JSONObject;
import android.content.SharedPreferences;
import java.util.HashMap;
import java.util.Map;
import java.io.IOException;

// OkHttp importları
import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class QRScannerActivity extends AppCompatActivity {
    private DecoratedBarcodeView barcodeView;
    private static final int CAMERA_PERMISSION_REQUEST = 100;
    private static final String API_URL = "https://qr.greensoft.ltd/kaydet.php";
    private final OkHttpClient client = new OkHttpClient();
    public static final MediaType JSON = MediaType.get("application/json; charset=utf-8");

    private BarcodeCallback callback = new BarcodeCallback() {
        @Override
        public void barcodeResult(BarcodeResult result) {
            if (result.getText() != null) {
                try {
                    // QR kod içeriğini UTF-8 formatında decode et
                    String jsonString = new String(result.getText().getBytes("UTF-8"), "UTF-8");

                    // JSON object olarak parse et
                    JSONObject doorInfo = new JSONObject(jsonString);
                    
                    // Kapı bilgilerini al
                    int id = doorInfo.getInt("doorId");
                    String name = doorInfo.getString("doorName");
                    String type = doorInfo.getString("doorType");
                    String timestamp = doorInfo.getString("timestamp");
                    String validUntil = doorInfo.getString("validUntil");
                    
                    // Alert Dialog oluştur
                    showDoorInfo(id, name, type, timestamp, validUntil);
                    
                    // Okuma işlemini durdur
                    barcodeView.pause();
                    
                } catch (Exception e) {
                    Log.e("QR_ERROR", "JSON Parse hatası: " + e.getMessage());
                    Toast.makeText(QRScannerActivity.this, 
                        "Geçersiz QR Kod formatı!\nHata: " + e.getMessage(), 
                        Toast.LENGTH_LONG).show();
                    // Hata durumunda kamerayı tekrar başlat
                    barcodeView.resume();
                }
            }
        }
    };

    private void sendDataToServer(int doorId, String doorName) {
        try {
            // Kullanıcı bilgilerini SharedPreferences'dan al
            SharedPreferences prefs = getSharedPreferences("UserData", MODE_PRIVATE);
            String userId = prefs.getString("userId", "");
            String userName = prefs.getString("userName", "");

            // POST verilerini hazırla
            JSONObject postData = new JSONObject();
            postData.put("personelId", userId);
            postData.put("personelAdi", String.valueOf(userName));
            postData.put("kapiId", doorId);   
            postData.put("kapiAdi", String.valueOf(doorName));

            String jsonString = postData.toString();
            Log.d("POST_DATA", "Gönderilen veri: " + jsonString);

            // Request body oluştur
            RequestBody body = RequestBody.create(jsonString, JSON);
            
            // Request oluştur
            Request request = new Request.Builder()
                    .url(API_URL)
                    .post(body)
                    .build();

            // Asenkron istek gönder
            client.newCall(request).enqueue(new Callback() {
                @Override
                public void onFailure(Call call, IOException e) {
                    Log.e("NETWORK_ERROR", "Ağ hatası: " + e.getMessage());
                    runOnUiThread(() -> {
                        Toast.makeText(QRScannerActivity.this,
                                "Sunucu bağlantı hatası: " + e.getMessage(),
                                Toast.LENGTH_LONG).show();
                    });
                }

                @Override
                public void onResponse(Call call, Response response) throws IOException {
                    try {
                        String responseData = response.body().string();
                        Log.d("SERVER_RESPONSE", "Sunucu yanıtı: " + responseData);
                        
                        JSONObject jsonResponse = new JSONObject(responseData);
                        Log.d("GELEN HATA",responseData);
                        boolean isSuccess = jsonResponse.getBoolean("success");
                        
                        runOnUiThread(() -> {
                            try {
                                if (isSuccess) {
                                    Toast.makeText(QRScannerActivity.this,
                                            "İşlem başarılı!",
                                            Toast.LENGTH_SHORT).show();
                                } else {
                                    String message = jsonResponse.optString("message", "İşlem başarısız!");
                                    Toast.makeText(QRScannerActivity.this,
                                            message,
                                            Toast.LENGTH_SHORT).show();
                                }
                            } catch (Exception e) {
                                Toast.makeText(QRScannerActivity.this,
                                        "Sunucu yanıtı işlenemedi!",
                                        Toast.LENGTH_SHORT).show();
                            }
                        });
                    } catch (Exception e) {
                        Log.e("JSON_ERROR", "JSON parse hatası: " + e.getMessage());
                        runOnUiThread(() -> {
                            Toast.makeText(QRScannerActivity.this,
                                    "Sunucu yanıtı işlenemedi!",
                                    Toast.LENGTH_SHORT).show();
                        });
                    } finally {
                        response.close();
                    }
                }
            });

        } catch (Exception e) {
            Log.e("POST_ERROR", "Veri gönderme hatası: " + e.getMessage());
            Toast.makeText(this,
                    "Veri gönderme hatası: " + e.getMessage(),
                    Toast.LENGTH_LONG).show();
        }
    }

    private void showDoorInfo(int id, String name, String type, String timestamp, String validUntil) {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        builder.setTitle("Kapı Bilgileri")
               .setMessage("Kapı ID: " + id + "\n" +
                         "Kapı Adı: " + name + "\n" +
                         "Kapı Tipi: " + type + "\n" +
                         "Oluşturma Zamanı: " + timestamp + "\n" +
                         "Geçerlilik Süresi: " + validUntil)
               .setPositiveButton("Tamam", (dialog, which) -> {
                   // Sunucuya veri gönder
                   sendDataToServer(id, name);
                   // Dialog kapandığında kamera taramayı tekrar başlat
                   barcodeView.resume();
               })
               .setCancelable(false)
               .show();
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_qr_scanner);

        barcodeView = findViewById(R.id.barcodeScanner);
        
        if (checkCameraPermission()) {
            startCamera();
        } else {
            requestCameraPermission();
        }
    }

    private boolean checkCameraPermission() {
        return ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) 
                == PackageManager.PERMISSION_GRANTED;
    }

    private void requestCameraPermission() {
        ActivityCompat.requestPermissions(this,
                new String[]{Manifest.permission.CAMERA},
                CAMERA_PERMISSION_REQUEST);
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, 
            @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == CAMERA_PERMISSION_REQUEST) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                startCamera();
            } else {
                Toast.makeText(this, "Kamera izni gerekli!", Toast.LENGTH_LONG).show();
                finish();
            }
        }
    }

    private void startCamera() {
        barcodeView.decodeContinuous(callback);
        barcodeView.resume();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (checkCameraPermission()) {
            barcodeView.resume();
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        barcodeView.pause();
    }
} 