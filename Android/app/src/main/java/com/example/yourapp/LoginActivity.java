package com.example.yourapp;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import java.util.HashMap;
import java.util.Map;

public class LoginActivity extends AppCompatActivity {
    private TextInputEditText usernameInput, passwordInput;
    private MaterialButton loginButton;
    
    // Kullanıcı verileri (ID, ad ve şifre)
    private static class UserData {
        String id;
        String name;
        String password;

        UserData(String id, String name, String password) {
            this.id = id;
            this.name = name;
            this.password = password;
        }
    }
    
    // Örnek kullanıcı verileri
    private final Map<String, UserData> users = new HashMap<String, UserData>() {{
        put("Ahmet", new UserData("1", "Ahmet", "1234"));
        put("Murat", new UserData("2", "Murat", "1234"));
        put("Huseyin", new UserData("3", "Huseyin", "1234"));
        put("Kamil", new UserData("4", "Kamil", "1234"));
        put("Zeki", new UserData("5", "Zeki", "1234"));
    }};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        usernameInput = findViewById(R.id.usernameInput);
        passwordInput = findViewById(R.id.passwordInput);
        loginButton = findViewById(R.id.loginButton);

        loginButton.setOnClickListener(v -> login());
    }

    private void login() {
        String username = usernameInput.getText().toString();
        String password = passwordInput.getText().toString();

        if (username.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Lütfen tüm alanları doldurun", Toast.LENGTH_SHORT).show();
            return;
        }

        UserData userData = users.get(username);
        if (userData != null && userData.password.equals(password)) {
            // Kullanıcı bilgilerini kaydet
            SharedPreferences prefs = getSharedPreferences("UserData", MODE_PRIVATE);
            SharedPreferences.Editor editor = prefs.edit();
            editor.putString("userId", userData.id);
            editor.putString("userName", userData.name);
            editor.apply();

            // QR Scanner sayfasına yönlendir
            Intent intent = new Intent(this, QRScannerActivity.class);
            startActivity(intent);
            finish();
        } else {
            Toast.makeText(this, "Geçersiz kullanıcı adı veya şifre", Toast.LENGTH_SHORT).show();
        }
    }
} 