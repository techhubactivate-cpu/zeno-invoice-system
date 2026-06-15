<?php
session_start();
// Only check if user is logged in, but DON'T modify the landing page display
// The landing page should remain the same for everyone until they manually login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeno - Get Paid Globally, Keep More of Your Money</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background: white;
        }
        
        /* Animated Gradient Background */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logo i {
            background: none;
            -webkit-text-fill-color: #667eea;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0%;
            height: 2px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.3s;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .btn-outline {
            padding: 0.6rem 1.8rem;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            padding: 0.6rem 1.8rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-large {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }
        
        .mobile-menu {
            display: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #667eea;
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 5% 80px 5%;
            margin-top: 0;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            z-index: 2;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(102,126,234,0.08) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .hero-content {
            flex: 1;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(102,126,234,0.15), rgba(118,75,162,0.15));
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(102,126,234,0.2);
        }
        
        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #1e3c72, #2a5298, #667eea);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 5s ease infinite;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            color: #4a5568;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 500px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .stat-item h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e3c72;
        }
        
        .stat-item p {
            font-size: 0.875rem;
            margin-bottom: 0;
            color: #718096;
        }
        
        .hero-image {
            flex: 1;
            position: relative;
            animation: fadeInRight 0.8s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .floating-card {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            position: absolute;
            animation: float 3s ease-in-out infinite;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        .floating-card:nth-child(1) {
            top: 5%;
            right: 5%;
            animation-delay: 0s;
        }
        
        .floating-card:nth-child(2) {
            bottom: 15%;
            left: 0%;
            animation-delay: 1s;
        }
        
        .floating-card:nth-child(3) {
            top: 40%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-card i {
            font-size: 1.2rem;
        }
        
        .floating-card .text-green {
            color: #48bb78;
        }
        
        .dashboard-preview {
            max-width: 90%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            background: white;
        }
        
        .dashboard-preview:hover {
            transform: scale(1.02);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 5%;
            background: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title h2 {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: #718096;
            font-size: 1.2rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            padding: 2rem;
            text-align: center;
            background: white;
            border-radius: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102,126,234,0.08), transparent);
            transition: left 0.5s;
        }
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s;
        }
        
        .feature-card:hover .feature-icon {
            transform: rotateY(180deg);
        }
        
        .feature-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1a202c;
        }
        
        .feature-card p {
            color: #718096;
            line-height: 1.6;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 6rem 5%;
            background: linear-gradient(135deg, #f5f7fa, #e9eef5);
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .step {
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .step-number::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            opacity: 0.3;
            z-index: -1;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }
        
        .step p {
            color: #718096;
        }
        
        /* Pricing */
        .pricing {
            padding: 6rem 5%;
            background: white;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .pricing-card.popular {
            border: 2px solid #667eea;
            transform: scale(1.05);
        }
        
        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-10px);
        }
        
        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .pricing-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .price {
            font-size: 3rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        
        .price span {
            font-size: 1rem;
            color: #718096;
        }
        
        .pricing-card ul {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .pricing-card li {
            padding: 0.6rem 0;
            color: #4a5568;
        }
        
        .pricing-card li i {
            color: #48bb78;
            margin-right: 0.5rem;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 5%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .cta h2 {
            font-size: 3rem;
            color: white;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .cta p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .cta .btn-primary {
            background: white;
            color: #667eea;
            position: relative;
            z-index: 1;
        }
        
        .cta .btn-primary:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
        
        /* Footer */
        .footer {
            background: #1a202c;
            color: white;
            padding: 4rem 5% 2rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-col h4 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .footer-col a {
            display: block;
            color: #a0aec0;
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .footer-col a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #667eea;
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #a0aec0;
        }
        
        /* ============ AUTH MODAL STYLES ============ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-container {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: modalSlideIn 0.3s ease;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .modal-header i {
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
        }
        
        .modal-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
        }
        
        .modal-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.2rem;
            cursor: pointer;
            font-size: 1.5rem;
            color: rgba(255,255,255,0.8);
            transition: color 0.3s;
            background: none;
            border: none;
            line-height: 1;
            z-index: 10;
        }
        
        .modal-close:hover {
            color: white;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #e6fffa;
            color: #234e52;
            border: 1px solid #b2f5ea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.95rem;
        }
        
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            background: white;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #2a5298;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
            border-radius: 2px;
        }
        
        .strength-text {
            font-size: 11px;
            margin-top: 5px;
            color: #718096;
        }
        
        .terms {
            margin: 15px 0;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #4a5568;
        }
        
        .checkbox-label input {
            margin-top: 2px;
        }
        
        .checkbox-label a {
            color: #2a5298;
            text-decoration: none;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .modal-switch {
            text-align: center;
            margin-top: 20px;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .modal-switch a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        
        .modal-switch a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .modal-body {
                padding: 1.5rem;
            }
            .modal-header {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 968px) {
            .hero-content h1 {
                font-size: 3rem;
            }
            
            .hero-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu {
                display: block;
            }
            
            .hero {
                flex-direction: column;
                text-align: center;
                padding: 100px 5% 60px 5%;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .floating-card {
                display: none;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .pricing-card.popular {
                transform: scale(1);
            }
            
            .pricing-card.popular:hover {
                transform: translateY(-10px);
            }
            
            .cta h2 {
                font-size: 2rem;
            }
            
            .modal-container {
                max-width: 95%;
            }
        }
        
        .dropdown-menu {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            background: white;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            z-index: 999;
            border-radius: 0 0 20px 20px;
        }
        
        .dropdown-menu.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-menu a {
            display: block;
            padding: 1rem;
            text-decoration: none;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
        }
        
        .dropdown-menu a:hover {
            background: #f7fafc;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-bolt"></i> ZENO
        </div>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#pricing">Pricing</a>
            <!-- Changed to onclick for modal -->
            <a onclick="openModal('login')" class="btn-outline">Login</a>
            <a onclick="openModal('signup')" class="btn-primary">Sign Up Free →</a>
        </div>
        <div class="mobile-menu" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="dropdown-menu" id="mobileDropdown">
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#pricing">Pricing</a>
        <a onclick="openModal('login'); document.getElementById('mobileDropdown').classList.remove('active');">Login</a>
        <a onclick="openModal('signup'); document.getElementById('mobileDropdown').classList.remove('active');">Sign Up Free</a>
    </div>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="badge">
                ⚡ Faster than fast. Smarter than smart.
            </div>
            <h1>Get Paid Globally.<br>Keep More of Your Money.</h1>
            <p>Stop losing 4-5% of every international payment. Zeno helps African freelancers invoice professionally and get paid faster with lower fees.</p>
            <div class="hero-buttons">
                <a onclick="openModal('signup')" class="btn-primary btn-large">Start Free Trial →</a>
                <a href="#how-it-works" class="btn-outline btn-large">See How It Works</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <h4>$5M+</h4>
                    <p>Processed Payments</p>
                </div>
                <div class="stat-item">
                    <h4>10,000+</h4>
                    <p>Active Freelancers</p>
                </div>
                <div class="stat-item">
                    <h4>120+</h4>
                    <p>Countries Served</p>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=450&fit=crop" alt="Global Payment Dashboard" class="dashboard-preview" style="object-fit: cover;">
            <div class="floating-card">
                <i class="fas fa-check-circle text-green"></i>
                <span>Invoice paid! $1,200 received</span>
            </div>
            <div class="floating-card">
                <i class="fas fa-bell" style="color: #667eea;"></i>
                <span>Payment reminder sent</span>
            </div>
            <div class="floating-card">
                <i class="fas fa-chart-line" style="color: #764ba2;"></i>
                <span>+22% this month</span>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Everything You Need to Get Paid</h2>
            <p>No more messy PDFs or expensive wire transfers</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3>Multi-Currency</h3>
                <p>Invoice in USD, EUR, GBP - your clients pay in their currency, you get paid in yours.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Instant Payments</h3>
                <p>Clients click to pay via Stripe, PayPal, Flutterwave - no more chasing payments.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Smart Analytics</h3>
                <p>Track income across currencies, get insights, and make data-driven decisions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Auto-Reminders</h3>
                <p>Automated payment reminders so you never have to chase clients again.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <h3>Pro Invoices</h3>
                <p>Branded, professional invoices that build trust with international clients.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Bank-Level Security</h3>
                <p>256-bit SSL encryption, PCI compliant - your money is safe.</p>
            </div>
        </div>
    </section>
    
    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-title">
            <h2>Get Paid in 3 Simple Steps</h2>
            <p>From invoice to payment in minutes, not weeks</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create Invoice</h3>
                <p>Fill in client details, amount, and currency. Choose your payment method.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Send Link</h3>
                <p>Share the payment link via email or WhatsApp. Client pays in their local currency.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Get Paid</h3>
                <p>Money arrives in your account. Track all payments from one dashboard.</p>
            </div>
        </div>
    </section>
    
    <!-- Pricing -->
    <section class="pricing" id="pricing">
        <div class="section-title">
            <h2>Simple, Transparent Pricing</h2>
            <p>No hidden fees. No surprises. Just fair pricing.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3>Free</h3>
                <div class="price">$0<span>/month</span></div>
                <ul>
                    <li><i class="fas fa-check"></i> 5 invoices/month</li>
                    <li><i class="fas fa-check"></i> 3 clients</li>
                    <li><i class="fas fa-check"></i> Basic support</li>
                    <li><i class="fas fa-check"></i> 1 currency</li>
                </ul>
                <a onclick="openModal('signup')" class="btn-outline" style="display: inline-block;">Get Started</a>
            </div>
            <div class="pricing-card popular">
                <div class="popular-badge">🔥 Most Popular</div>
                <h3>Pro</h3>
                <div class="price">$19<span>/month</span></div>
                <ul>
                    <li><i class="fas fa-check"></i> Unlimited invoices</li>
                    <li><i class="fas fa-check"></i> Unlimited clients</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                    <li><i class="fas fa-check"></i> Multi-currency</li>
                    <li><i class="fas fa-check"></i> Payment reminders</li>
                    <li><i class="fas fa-check"></i> Advanced analytics</li>
                </ul>
                <a onclick="openModal('signup')" class="btn-primary" style="display: inline-block;">Start Free Trial</a>
            </div>
            <div class="pricing-card">
                <h3>Business</h3>
                <div class="price">$49<span>/month</span></div>
                <ul>
                    <li><i class="fas fa-check"></i> Everything in Pro</li>
                    <li><i class="fas fa-check"></i> API access</li>
                    <li><i class="fas fa-check"></i> Team accounts (up to 5)</li>
                    <li><i class="fas fa-check"></i> Custom branding</li>
                    <li><i class="fas fa-check"></i> Dedicated account manager</li>
                </ul>
                <a onclick="openModal('signup')" class="btn-outline" style="display: inline-block;">Get Started</a>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Stop Losing Money to Fees?</h2>
        <p>Join thousands of African freelancers already using Zeno</p>
        <a onclick="openModal('signup')" class="btn-primary btn-large">Start Your Free Trial →</a>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h4><i class="fas fa-bolt"></i> ZENO</h4>
                <p>Empowering African freelancers with global invoicing solutions.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#">Integrations</a>
                <a href="#">API</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a href="#">About Us</a>
                <a href="#">Blog</a>
                <a href="#">Careers</a>
                <a href="#">Press</a>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Contact Us</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2026 ZENO. All rights reserved. Faster than fast. Smarter than smart.</p>
        </div>
    </footer>

    <!-- ============ AUTH MODAL ============ -->
    <div class="modal-overlay" id="authModal">
        <div class="modal-container">
            <!-- Login Modal Header -->
            <div class="modal-header" id="modalHeaderLogin">
                <button class="modal-close" onclick="closeModal()">&times;</button>
                <i class="fas fa-sign-in-alt"></i>
                <h2>Welcome Back 👋</h2>
                <p>Login to your Zeno account</p>
            </div>
            <!-- Signup Modal Header -->
            <div class="modal-header" id="modalHeaderSignup" style="display:none;">
                <button class="modal-close" onclick="closeModal()">&times;</button>
                <i class="fas fa-bolt"></i>
                <h2>Create Account</h2>
                <p>Join African freelancers getting paid globally</p>
            </div>
            
            <div class="modal-body">
                <!-- Login Form -->
                <div id="loginForm">
                    <div class="alert alert-error" id="loginError"></div>
                    <form onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="loginEmail" required autocomplete="email">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="loginPassword" required autocomplete="current-password">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit" id="loginBtn">Login to Dashboard</button>
                    </form>
                    <p class="modal-switch">Don't have an account? <a onclick="switchForm('signup')">Sign Up Free</a></p>
                </div>
                
                <!-- Signup Form (hidden by default) -->
                <div id="signupForm" style="display:none;">
                    <div class="alert alert-error" id="signupError"></div>
                    <form onsubmit="handleSignup(event)">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="signupFirstName" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="signupLastName" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="signupEmail" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="input-group">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="signupPhone">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Country *</label>
                            <div class="input-group">
                                <i class="fas fa-map-marker-alt"></i>
                                <select id="signupCountry" required>
                                    <option value="">Select Country</option>
                                    <option>Kenya</option>
                                    <option>Nigeria</option>
                                    <option>South Africa</option>
                                    <option>Ghana</option>
                                    <option>Egypt</option>
                                    <option>Morocco</option>
                                    <option>Tanzania</option>
                                    <option>Uganda</option>
                                    <option>Rwanda</option>
                                    <option>Ethiopia</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>I am a *</label>
                            <div class="input-group">
                                <i class="fas fa-briefcase"></i>
                                <select id="signupAccountType" required>
                                    <option value="">Select your profession</option>
                                    <option value="freelancer">Freelancer</option>
                                    <option value="creative">Creative (Designer, Writer, Artist)</option>
                                    <option value="developer">Software Developer</option>
                                    <option value="consultant">Consultant</option>
                                    <option value="agency">Digital Agency</option>
                                    <option value="small_business">Small Business Owner</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="signupPassword" required minlength="8">
                            </div>
                            <div class="password-strength"><div class="strength-bar" id="strengthBar"></div></div>
                            <div class="strength-text" id="strengthText"></div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <div class="input-group">
                                <i class="fas fa-check-circle"></i>
                                <input type="password" id="signupConfirmPassword" required>
                            </div>
                        </div>
                        <div class="terms">
                            <label class="checkbox-label">
                                <input type="checkbox" id="signupTerms" required>
                                <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                            </label>
                        </div>
                        <button type="submit" class="btn-submit" id="signupBtn">Create Free Account</button>
                    </form>
                    <p class="modal-switch">Already have an account? <a onclick="switchForm('login')">Sign in</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileDropdown');
            menu.classList.toggle('active');
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    document.getElementById('mobileDropdown').classList.remove('active');
                }
            });
        });
        
        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
            }
        });

        // ============ AUTH MODAL FUNCTIONS ============
        
        function openModal(type) {
            document.getElementById('authModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            switchForm(type);
        }

        function closeModal() {
            document.getElementById('authModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function switchForm(type) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const loginHeader = document.getElementById('modalHeaderLogin');
            const signupHeader = document.getElementById('modalHeaderSignup');
            
            if (type === 'login') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                loginHeader.style.display = 'block';
                signupHeader.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                loginHeader.style.display = 'none';
                signupHeader.style.display = 'block';
            }
            
            // Clear errors
            document.getElementById('loginError').style.display = 'none';
            document.getElementById('signupError').style.display = 'none';
        }

        // ============ LOGIN ============
        async function handleLogin(e) {
            e.preventDefault();
            
            const errorEl = document.getElementById('loginError');
            const btn = document.getElementById('loginBtn');
            errorEl.style.display = 'none';
            
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            
            if (!email || !password) {
                errorEl.textContent = 'Please fill in all fields.';
                errorEl.style.display = 'block';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Logging in...';
            
            try {
                const formData = new URLSearchParams();
                formData.append('email', email);
                formData.append('password', password);
                formData.append('login_submit', '1');
                
                const res = await fetch('login.php', {
                    method: 'POST',
                   headers: { 
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-Requested-With': 'XMLHttpRequest'
},
                    body: formData
                });
                
                const text = await res.text();
                
                // Check if the response is JSON (error) or HTML redirect (success)
                try {
                    const json = JSON.parse(text);
                    if (json.error) {
                        errorEl.textContent = json.error;
                        errorEl.style.display = 'block';
                    } else if (json.redirect) {
                        window.location.href = json.redirect;
                    }
                } catch {
                    // Not JSON — likely a redirect happened, check by trying dashboard
                    // If session was set, we can go to dashboard
                    window.location.href = 'dashboard.php';
                }
            } catch (err) {
                errorEl.textContent = 'Network error. Please try again.';
                errorEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Login to Dashboard';
            }
        }

        // ============ SIGNUP ============
        async function handleSignup(e) {
            e.preventDefault();
            
            const errorEl = document.getElementById('signupError');
            const btn = document.getElementById('signupBtn');
            errorEl.style.display = 'none';
            
            const firstName = document.getElementById('signupFirstName').value.trim();
            const lastName = document.getElementById('signupLastName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();
            const phone = document.getElementById('signupPhone').value.trim();
            const country = document.getElementById('signupCountry').value;
            const accountType = document.getElementById('signupAccountType').value;
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;
            const terms = document.getElementById('signupTerms').checked;
            
            // Client-side validation
            if (!firstName || !lastName) {
                errorEl.textContent = 'First name and last name are required.';
                errorEl.style.display = 'block';
                return;
            }
            if (!email) {
                errorEl.textContent = 'Email address is required.';
                errorEl.style.display = 'block';
                return;
            }
            if (password.length < 8) {
                errorEl.textContent = 'Password must be at least 8 characters.';
                errorEl.style.display = 'block';
                return;
            }
            if (password !== confirmPassword) {
                errorEl.textContent = 'Passwords do not match.';
                errorEl.style.display = 'block';
                return;
            }
            if (!terms) {
                errorEl.textContent = 'You must agree to the Terms of Service.';
                errorEl.style.display = 'block';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Creating account...';
            
            try {
                const formData = new URLSearchParams();
                formData.append('first_name', firstName);
                formData.append('last_name', lastName);
                formData.append('email', email);
                formData.append('phone', phone);
                formData.append('country', country);
                formData.append('account_type', accountType);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
                formData.append('terms_checkbox', 'on');
                formData.append('signup_submit', '1');
                
                const res = await fetch('signup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                
                const text = await res.text();
                
                // Try to parse as JSON first (for errors)
                try {
                    const json = JSON.parse(text);
                    if (json.error) {
                        errorEl.textContent = json.error;
                        errorEl.style.display = 'block';
                    } else if (json.redirect) {
                        window.location.href = json.redirect;
                    }
                } catch {
                    // Not JSON — check if we got redirected (success)
                    // Your signup.php redirects to login.php on success, then login.php could auto-login
                    // Try going to dashboard — if session was set, it'll work
                    window.location.href = 'dashboard.php';
                }
            } catch (err) {
                errorEl.textContent = 'Network error. Please check your connection.';
                errorEl.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create Free Account';
            }
        }

        // ============ PASSWORD STRENGTH METER ============
        document.getElementById('signupPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            const width = (strength / 5) * 100;
            strengthBar.style.width = width + '%';
            
            if (strength <= 2) {
                strengthBar.style.background = '#e53e3e';
                strengthText.textContent = 'Weak password';
            } else if (strength <= 4) {
                strengthBar.style.background = '#ed8936';
                strengthText.textContent = 'Medium password';
            } else {
                strengthBar.style.background = '#48bb78';
                strengthText.textContent = 'Strong password';
            }
        });

        // Close modal when clicking outside
        document.getElementById('authModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>