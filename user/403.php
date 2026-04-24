<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Menggunakan bootstrap untuk helpers
// $user_data, $isAdmin sudah tersedia dari bootstrap.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - 403</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>"> <!-- Menggunakan CSS utama -->
    <style>
        :root {
            --bg-primary: #fff;
            --text-light: #333;
            --old-primary: #39ac31;
            --old-secondary: #2e8a27;
            --text-secondary: #6c757d;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Arvo', serif;
            background: var(--bg-primary);
            color: var(--text-light);
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .page_404 {
            padding: 2.5rem 0;
            background: var(--bg-primary);
            width: 100%;
        }

        .four_zero_four_bg {
            background-image: url(https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif);
            height: 300px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            max-width: 100%;
        }
        
        .four_zero_four_bg h1 {
            font-size: 5rem;
            margin: 0;
            color: var(--text-light);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .contant_box_404 {
            margin-top: -3.125rem;
            text-align: center;
            padding: 0 15px;
        }
        
        .contant_box_404 h3 {
            font-size: 1.875rem;
            margin-bottom: 1.25rem;
            color: var(--text-light);
        }
        
        .link_404 {
            color: #fff !important;
            padding: 0.625rem 1.25rem;
            background: var(--old-primary);
            border-radius: 0.3125rem;
            display: inline-block;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 20px 0;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .link_404:hover {
            background: var(--old-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .text-secondary {
            color: var(--text-secondary) !important;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        /* Media Queries untuk Responsivitas */
        @media (max-width: 768px) {
            .four_zero_four_bg {
                height: 250px;
            }
            
            .four_zero_four_bg h1 {
                font-size: 4rem;
            }
            
            .contant_box_404 h3 {
                font-size: 1.5rem;
            }
            
            .text-secondary {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .page_404 {
                padding: 1.5rem 0;
            }
            
            .four_zero_four_bg {
                height: 200px;
            }
            
            .four_zero_four_bg h1 {
                font-size: 3.5rem;
            }
            
            .contant_box_404 {
                margin-top: -2rem;
            }
            
            .contant_box_404 h3 {
                font-size: 1.3rem;
            }
            
            .link_404 {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 400px) {
            .four_zero_four_bg {
                height: 180px;
            }
            
            .four_zero_four_bg h1 {
                font-size: 3rem;
            }
            
            .contant_box_404 h3 {
                font-size: 1.2rem;
            }
            
            .text-secondary {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <section class="page_404">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="col-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 text-center">
                        <div class="four_zero_four_bg">
                            <h1>403</h1>
                        </div>

                        <div class="contant_box_404">
                            <h3>Akses Ditolak</h3>

                            <p class="text-secondary">Anda tidak memiliki izin untuk mengakses halaman ini! atau mungkin sedang tahap pengembangan</p>

                            <a href="<?= url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php') ?>" class="link_404">Kembali ke Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>