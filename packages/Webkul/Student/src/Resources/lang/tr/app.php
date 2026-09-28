<?php

return [
    'acl' => [
        'students' => 'Öğrenciler',
        'create' => 'Oluştur',
        'edit' => 'Düzenle',
        'view' => 'Görüntüle',
        'delete' => 'Sil',
    ],

    'students' => [
        'title' => 'Öğrenciler',
        'create-success' => 'Öğrenci başarıyla oluşturuldu.',
        'update-success' => 'Öğrenci başarıyla güncellendi.',
        'delete-success' => 'Öğrenci başarıyla silindi.',
        'delete-failed' => 'Öğrenci silinemedi.',
        'all-delete-success' => 'Seçilen öğrenciler başarıyla silindi.',
        'no-selection' => 'Hiçbir öğrenci seçilmedi.',

        'index' => [
            'title' => 'Öğrenciler',
            'create-btn' => 'Öğrenci Ekle',

            'datagrid' => [
                'id' => 'Kimlik',
                'name' => 'Ad Soyad',
                'university-card-number' => 'Üniversite Kart Numarası',
                'registration-number' => 'Kayıt Numarası',
                'major' => 'Bölüm',
                'academic-level' => 'Akademik Seviye',
                'created-at' => 'Oluşturulma Tarihi',
                'view' => 'Görüntüle',
                'edit' => 'Düzenle',
                'delete' => 'Sil',
            ],
        ],

        'create' => [
            'title' => 'Öğrenci Ekle',
            'save-btn' => 'Öğrenciyi Kaydet',
        ],

        'edit' => [
            'title' => 'Öğrenciyi Düzenle',
            'save-btn' => 'Değişiklikleri Kaydet',
        ],

        'view' => [
            'title' => 'Öğrenci: :name',
            'heading' => 'Öğrenci Detayları',
            'edit-btn' => 'Öğrenciyi Düzenle',
            'general-info' => 'Genel Bilgiler',
        ],

        'form' => [
            'name' => 'Ad Soyad',
            'university-card-number' => 'Üniversite Kart Numarası',
            'registration-number' => 'Kayıt Numarası',
            'major' => 'Bölüm',
            'academic-level' => 'Akademik Seviye',
            'password' => 'Şifre',
            'password-confirmation' => 'Şifre Onayı',
            'profile-image' => 'Profil Resmi',
        ],
    ],

    'configuration' => [
        'student-login' => [
            'title' => 'Öğrenci Giriş Sayfası',
            'info' => 'Öğrenci portalı giriş sayfası markalama ve içerik ayarları.',
            'logo-image' => 'Giriş Logosu',
            'primary-color' => 'Birincil Renk',
            'accent-color' => 'Vurgu Rengi',
            'surface-start' => 'Yüzey Gradyanı Başlangıcı',
            'surface-end' => 'Yüzey Gradyanı Bitişi',
            'panel-start' => 'Yan Panel Gradyanı Başlangıcı',
            'panel-end' => 'Yan Panel Gradyanı Bitişi',
            'field-title' => 'Başlık Metni',
            'description' => 'Açıklama Metni',
            'eyebrow' => 'Ön Başlık',
            'panel-lead' => 'Yan Panel Giriş Paragrafı',
            'card-number' => 'Kart Numarası Alanı Etiketi',
            'password' => 'Şifre Alanı Etiketi',
            'remember' => 'Beni Hatırla Etiketi',
            'submit' => 'Giriş Butonu Etiketi',
            'back-portal' => 'Geri Butonu Etiketi',
        ],

        'university-api' => [
            'title' => 'Üniversite API Entegrasyonu',
            'info' => 'Uzak üniversite öğrenci doğrulama uç noktası ayarları.',
            'endpoint-settings' => [
                'title' => 'Uç Nokta Ayarları',
                'info' => 'Doğrulama uç noktası URL yapılandırması.',
                'endpoint' => 'API Doğrulama Uç Noktası',
                'endpoint-info' => 'Öğrenci doğrulama için tam URL adresini girin.',
            ],
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'mega-search' => [
                    'explore-all-students' => 'Tüm Öğrencileri Keşfet',
                ],
            ],
        ],
    ],

    'login' => [
        'title' => 'Öğrenci Girişi',
        'description' => 'Üniversite kart numaranızı ve üniversiteniz tarafından verilen şifrenizi kullanın.',
        'eyebrow' => 'Güvenli erişim',
        'panel_title' => 'Kampüs portalınız',
        'panel_lead' => 'Etkinlikler, duyurular ve ihtiyacınız olan her şey tek bir yerde.',
        'feature_verify' => 'İlk girişte üniversite onaylı kimlik doğrulaması',
        'feature_profile' => 'Resmi öğrenci kaydınızla senkronize profil',
        'feature_portal' => 'Öğrenci portalına kesintisiz erişim',
        'trust_note' => 'Kimlik bilgileriniz kurumunuz ile doğrulanır.',
        'back_portal' => 'Ana sayfaya dön',
        'show_password' => 'Şifreyi göster',
        'hide_password' => 'Şifreyi gizle',
        'card_number' => 'Üniversite kart numarası',
        'password' => 'Şifre',
        'remember' => 'Beni hatırla',
        'submit' => 'Giriş Yap',
        'failed' => 'Bu kimlik bilgileri kayıtlarımızla eşleşmiyor.',
        'welcome_back' => 'Tekrar hoş geldiniz.',
        'registered' => 'Hesabınız oluşturuldu. Hoş geldiniz.',
        'logged_out' => 'Çıkış yapıldı.',
    ],

    'university' => [
        'unavailable' => 'Üniversite doğrulama servisi şu anda kullanılamıyor.',
        'invalid_credentials' => 'Üniversite bu kimlik bilgilerini kabul etmedi.',
        'invalid_response' => 'Üniversite sunucusundan beklenmeyen yanıt.',
    ],
];
