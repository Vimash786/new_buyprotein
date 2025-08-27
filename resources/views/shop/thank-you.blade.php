@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@endpush

@section('content')
    <style>
        .thank-you-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .thank-you-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .thank-you-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #009ec9, #28a745, #009ec9);
        }

        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            }
        }

        .success-icon i {
            font-size: 48px;
            color: white;
        }

        .thank-you-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2C3C28;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .thank-you-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 40px 0;
            border-left: 5px solid #009ec9;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 600;
            color: #009ec9;
            margin-bottom: 15px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            text-align: left;
        }

        .info-label {
            font-weight: 600;
            color: #2C3C28;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #495057;
            font-size: 1.2rem;
        }

        .next-steps {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 15px;
            padding: 30px;
            margin: 40px 0;
        }

        .next-steps h4 {
            color: #2C3C28;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .steps-list {
            text-align: left;
            list-style: none;
            padding: 0;
        }

        .steps-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .steps-list li:last-child {
            border-bottom: none;
        }

        .step-number {
            background: #009ec9;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn-action {
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary-action {
            background: linear-gradient(135deg, #009ec9, #0056b3);
            color: white;
        }

        .btn-primary-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 158, 201, 0.3);
            color: white;
        }

        .btn-secondary-action {
            background: white;
            color: #009ec9;
            border: 2px solid #009ec9;
        }

        .btn-secondary-action:hover {
            background: #009ec9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 158, 201, 0.2);
        }

        .contact-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .contact-info h5 {
            color: #856404;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .contact-info p {
            color: #856404;
            margin: 0;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .social-link:hover {
            transform: translateY(-3px);
            color: white;
        }

        .social-link.facebook { background: #3b5998; }
        .social-link.twitter { background: #1da1f2; }
        .social-link.instagram { background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); }
        .social-link.whatsapp { background: #25d366; }

        @media (max-width: 768px) {
            .thank-you-card {
                padding: 40px 20px;
                margin: 20px;
            }
            
            .thank-you-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
                max-width: 300px;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 10%;
            left: 15%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
    </style>

    <div class="thank-you-container">
        <div class="thank-you-card">
            <div class="floating-elements">
                <div class="floating-element">🎉</div>
                <div class="floating-element">✨</div>
                <div class="floating-element">🛍️</div>
            </div>

            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="thank-you-title">Thank You for Your Order!</h1>
            <p class="thank-you-subtitle">
                Your order has been successfully placed and is being processed. We're excited to get your products to you as soon as possible!
            </p>

            @if(session('order_details'))
                @php $orderDetails = session('order_details') @endphp
                <div class="order-details">
                    <div class="order-number">Order #{{ $orderDetails['order_number'] ?? 'N/A' }}</div>
                    
                    <div class="order-info">
                        <div class="info-item">
                            <div class="info-label">Order Date</div>
                            <div class="info-value">{{ date('M d, Y') }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value">
                                @if(($orderDetails['payment_method'] ?? '') === 'cod')
                                    Cash on Delivery
                                @else
                                    Online Payment
                                @endif
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value">₹{{ number_format($orderDetails['total_amount'] ?? 0, 2) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $orderDetails['email'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="next-steps">
                <h4><i class="fas fa-clipboard-list"></i> What Happens Next?</h4>
                <ul class="steps-list">
                    <li>
                        <span class="step-number">1</span>
                        <span>Order confirmation email sent to your registered email address</span>
                    </li>
                    <li>
                        <span class="step-number">2</span>
                        <span>Our team will process and pack your order within 1-2 business days</span>
                    </li>
                    <li>
                        <span class="step-number">3</span>
                        <span>You'll receive tracking information once your order ships</span>
                    </li>
                    <li>
                        <span class="step-number">4</span>
                        <span>Expected delivery within 3-7 business days</span>
                    </li>
                </ul>
            </div>

            <div class="action-buttons">
                @auth
                    <a href="{{ route('user.orders') }}" class="btn-action btn-primary-action">
                        <i class="fas fa-box"></i>
                        Track Your Order
                    </a>
                @endauth
                <a href="{{ route('shop') }}" class="btn-action btn-secondary-action">
                    <i class="fas fa-shopping-cart"></i>
                    Continue Shopping
                </a>
            </div>

            <div class="contact-info">
                <h5><i class="fas fa-headset"></i> Need Help?</h5>
                <p>Our customer support team is here to help! Contact us at <strong>support@buyprotein.com</strong> or call us at <strong>+91-81285 30460</strong></p>
            </div>

            <div class="social-links">
                <a href="#" class="social-link facebook" title="Follow us on Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link twitter" title="Follow us on Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link instagram" title="Follow us on Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-link whatsapp" title="WhatsApp Support">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Add some interactive effects
    document.addEventListener('DOMContentLoaded', function() {
        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.order-details, .next-steps, .action-buttons').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        // Add confetti effect (optional)
        setTimeout(() => {
            createConfetti();
        }, 500);
    });

    function createConfetti() {
        const colors = ['#009ec9', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
        const confettiContainer = document.createElement('div');
        confettiContainer.style.position = 'fixed';
        confettiContainer.style.top = '0';
        confettiContainer.style.left = '0';
        confettiContainer.style.width = '100%';
        confettiContainer.style.height = '100%';
        confettiContainer.style.pointerEvents = 'none';
        confettiContainer.style.zIndex = '9999';
        document.body.appendChild(confettiContainer);

        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'absolute';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.top = '-10px';
            confetti.style.borderRadius = '50%';
            confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
            confettiContainer.appendChild(confetti);
        }

        // Remove confetti after animation
        setTimeout(() => {
            document.body.removeChild(confettiContainer);
        }, 5000);
    }

    // Add CSS for confetti animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
</script>
@endsection
