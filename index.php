<?php
// Start session if needed, though this is primarily a static landing page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Poultry - Premium Farm Products</title>
    <!-- SEO Meta Tags -->
    <meta name="description" content="Experience the finest selection of premium, ethically raised poultry products. Farm fresh quality delivered straight to your door.">
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --dark: #0f172a;
            --dark-glass: rgba(15, 23, 42, 0.7);
            --light: #f8fafc;
            --white-glass: rgba(255, 255, 255, 0.05);
            --text-gray: #94a3b8;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Navbar */
        nav {
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            background: var(--dark-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1.25rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--light);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a.nav-item::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
        }

        .nav-links a.nav-item:hover {
            color: var(--primary);
        }

        .nav-links a.nav-item:hover::after {
            width: 100%;
        }

        .btn {
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--dark) !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary) !important;
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--dark) !important;
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 5%;
            position: relative;
            background: url('uploads/hero_bg.png') center/cover no-repeat;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.6) 50%, rgba(15, 23, 42, 0.1) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 650px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 1s cubic-bezier(0.4, 0, 0.2, 1) forwards 0.3s;
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(245, 158, 11, 0.15);
            color: var(--primary);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(245, 158, 11, 0.3);
            backdrop-filter: blur(4px);
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-gray);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-btns {
            display: flex;
            gap: 1rem;
        }

        /* Features Section */
        .features {
            padding: 6rem 5%;
            background: var(--dark);
            position: relative;
            z-index: 2;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-gray);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--white-glass);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 3rem 2rem;
            border-radius: 20px;
            text-align: center;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(245, 158, 11, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(245, 158, 11, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.25rem;
            margin: 0 auto 1.5rem;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            background: var(--primary);
            color: var(--dark);
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-gray);
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #090e1a;
            padding: 4rem 5% 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .footer-content p {
            color: var(--text-gray);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }

        .social-links a {
            color: var(--text-gray);
            font-size: 1.5rem;
            transition: var(--transition);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .social-links a:hover {
            color: var(--primary);
            background: rgba(245, 158, 11, 0.1);
            transform: translateY(-3px);
        }

        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none; /* Basic mobile handling */
            }
            .hero h1 {
                font-size: 2.75rem;
            }
            .hero-btns {
                flex-direction: column;
            }
            .hero-content {
                text-align: center;
                margin: 0 auto;
            }
            .hero::before {
                background: linear-gradient(to bottom, rgba(15, 23, 42, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%);
            }
        }
    </style>
</head>
<body>

    <nav id="navbar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-feather"></i>
            PremiumPoultry
        </a>
        <div class="nav-links">
            <a href="#home" class="nav-item">Home</a>
            <a href="#features" class="nav-item">Why Us</a>
            <div style="width: 1px; height: 24px; background: rgba(255,255,255,0.2); margin: 0 0.5rem;"></div>
            <a href="user/login.php" class="btn btn-outline">Log In</a>
            <a href="user/register.php" class="btn btn-primary">Register</a>
            <a href="admin/login.php" style="font-size: 0.9rem; color: var(--text-gray); margin-left: 0.5rem; padding: 0.5rem;" title="Admin Portal">
                <i class="fa-solid fa-user-shield"></i>
            </a>
        </div>
    </nav>

    <main>
        <section class="hero" id="home">
            <div class="hero-content">
                <div class="hero-tag">
                    <i class="fa-solid fa-leaf"></i> 100% Organic & Farm Fresh
                </div>
                <h1>Quality Poultry,<br>Delivered Fresh.</h1>
                <p>Experience the finest selection of premium, ethically raised poultry products. From farm to your table, we guarantee freshness, taste, and exceptional quality in every single order.</p>
                <div class="hero-btns">
                    <a href="user/login.php" class="btn btn-primary">
                        Start Shopping <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="#features" class="btn btn-outline">
                        Learn More
                    </a>
                </div>
            </div>
        </section>

        <section class="features" id="features">
            <div class="section-header">
                <h2>Why Choose PremiumPoultry?</h2>
                <p>We take pride in delivering the highest quality poultry products combined with an exceptional customer experience.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-tractor"></i>
                    </div>
                    <h3>Farm Fresh</h3>
                    <p>Directly sourced from our local, free-range farms ensuring peak freshness and unparalleled taste for your family.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3>Quality Assured</h3>
                    <p>Every product goes through rigorous quality and health checks. We maintain the highest standards of hygiene and safety.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <h3>Express Delivery</h3>
                    <p>Fast, reliable, and temperature-controlled delivery to ensure your products arrive in pristine condition.</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-feather"></i>
                PremiumPoultry
            </a>
            <p>Providing the best farm-fresh poultry directly to your doorstep.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 PremiumPoultry Shop. All rights reserved.
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(15, 23, 42, 0.95)';
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.5)';
                navbar.style.padding = '1rem 5%';
            } else {
                navbar.style.background = 'rgba(15, 23, 42, 0.7)';
                navbar.style.boxShadow = 'none';
                navbar.style.padding = '1.25rem 5%';
            }
        });
    </script>
</body>
</html>
