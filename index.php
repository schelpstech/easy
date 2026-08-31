<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$homeQuoteState = pull_form_state('quote');
$homeContactState = pull_form_state('contact');
?>
<!doctype html>
<html lang="en" class="easyway-home-root">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Meta Information for SEO -->
    <meta name="description"
        content="Easyway Logistics offers reliable and affordable delivery services within Nigeria and internationally. Fast shipping, real-time tracking, and excellent customer support.">
    <meta name="keywords"
        content="Easyway Logistics, courier, delivery, shipping, Nigeria, international delivery, logistics, parcel tracking, freight services">
    <meta name="author" content="Easyway Logistics">

    <!-- Load the premium variable typefaces ahead of the application styles. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&amp;family=SUSE:wght@100..800&amp;display=swap">

    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/jquery-ui.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">

    <!-- Animation and Effects -->
    <link href="assets/css/animate.min.css" rel="stylesheet">
    <link href="assets/css/jquery.fancybox.min.css" rel="stylesheet">

    <!-- Sliders -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/slick.css">
    <link rel="stylesheet" href="assets/css/slick-theme.css">

    <!-- Date Picker & Select -->
    <link rel="stylesheet" href="assets/css/daterangepicker.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">

    <!-- Icons -->
    <link href="assets/css/boxicons.min.css" rel="stylesheet">

    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/stage1.css">

    <!-- Title -->
    <title>Easyway Logistics – Reliable Delivery Within & Outside Nigeria</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/easyway/logo.jpg" type="image/png" sizes="32x32">
</head>


<body class="tt-magic-cursor easyway-home">

    <div id="magic-cursor">
        <div id="ball"></div>
    </div>

    <!-- Back To Top -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
        <svg class="arrow" width="22" height="25" viewBox="0 0 24 23" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M0.556131 11.4439L11.8139 0.186067L13.9214 2.29352L13.9422 20.6852L9.70638 20.7061L9.76793 8.22168L3.6064 14.4941L0.556131 11.4439Z" />
            <path d="M23.1276 11.4999L16.0288 4.40105L15.9991 10.4203L20.1031 14.5243L23.1276 11.4999Z" />
        </svg>
    </div>

    <!-- header Section Start-->
    <header class="header-area style-4">
        <div class="container d-flex flex-nowrap align-items-center justify-content-between">
            <div class="logo-and-menu-area">
                <a href="<?= e(url('index.php')) ?>" class="header-logo">
                    <img src="assets/img/easyway/logo.jpg" alt="Easyway Logistics">
                </a>
                <div class="main-menu">
                    <div class="mobile-logo-area d-xl-none d-flex align-items-center justify-content-between">
                        <a href="<?= e(url('index.php')) ?>" class="mobile-logo-wrap">
                            <img src="assets/img/easyway/logo.jpg" alt="Easyway Logistics">
                        </a>
                        <div class="menu-close-btn">
                            <i class="bi bi-x"></i>
                        </div>
                    </div>
                    <ul class="menu-list">
                        <li><a href="#home">Home</a></li>
                        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                        <li class="menu-item-has-children">
                            <a href="<?= e(url('services.php')) ?>">Solutions</a>
                            <i class="bi bi-plus dropdown-icon"></i>
                            <ul class="sub-menu">
                                <li><a href="<?= e(url('services.php')) ?>">Delivery Services</a></li>
                                <li><a href="<?= e(url('destinations.php')) ?>">International Destinations</a></li>
                                <li><a href="<?= e(url('cargo-services.php')) ?>">Cargo Services</a></li>
                                <li><a href="<?= e(url('packaging-materials.php')) ?>">Packaging Materials</a></li>
                                <li><a href="<?= e(url('calculator.php')) ?>">Shipping Calculator</a></li>
                            </ul>
                        </li>
                        <li><a href="<?= e(url('tracking.php')) ?>">Tracking</a></li>
                        <li><a href="<?= e(url('quote.php')) ?>">Get Quote</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                    </ul>
                    <div class="language-area d-xl-none d-flex">
                        <div class="language-btn">
                            <div class="icon-and-content">
                                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path
                                            d="M7 14C5.13023 14 3.37239 13.2719 2.05023 11.9498C0.728137 10.6276 0 8.86977 0 7C0 5.13023 0.728137 3.37239 2.05023 2.05023C3.37239 0.728137 5.13023 0 7 0C8.86977 0 10.6276 0.728137 11.9498 2.05023C13.2719 3.37239 14 5.13023 14 7C14 8.86977 13.2719 10.6276 11.9498 11.9498C10.6276 13.2719 8.86977 14 7 14ZM7 0.583324C3.46183 0.583324 0.583324 3.46183 0.583324 7C0.583324 10.5382 3.46183 13.4166 7 13.4166C10.5382 13.4166 13.4166 10.5382 13.4166 7C13.4166 3.46183 10.5382 0.583324 7 0.583324Z">
                                        </path>
                                        <path
                                            d="M7 14C5.90297 14 4.8854 13.2486 4.13468 11.8841C3.41431 10.5747 3.01758 8.84018 3.01758 7C3.01758 5.15982 3.41431 3.42527 4.13468 2.11589C4.8854 0.751433 5.90297 0 7 0C8.09704 0 9.11461 0.751433 9.8653 2.11589C10.5857 3.42527 10.9824 5.15982 10.9824 7C10.9824 8.84018 10.5857 10.5747 9.8653 11.8841C9.11461 13.2486 8.09704 14 7 14ZM7 0.583324C6.12536 0.583324 5.2893 1.22746 4.64579 2.39709C3.97198 3.62179 3.6009 5.25645 3.6009 7C3.6009 8.74355 3.97198 10.3782 4.64576 11.6029C5.28927 12.7725 6.12533 13.4166 6.99998 13.4166C7.87462 13.4166 8.71068 12.7725 9.35419 11.6029C10.028 10.3782 10.3991 8.74355 10.3991 7C10.3991 5.25645 10.028 3.62179 9.35419 2.39709C8.71071 1.22746 7.87462 0.583324 7 0.583324Z">
                                        </path>
                                        <path
                                            d="M6.99968 13.9573C6.8386 13.9573 6.70801 13.8267 6.70801 13.6657V0.334156C6.70801 0.173074 6.83857 0.0424805 6.99968 0.0424805C7.16077 0.0424805 7.29136 0.173074 7.29136 0.334156V13.6657C7.29136 13.8267 7.16077 13.9573 6.99968 13.9573Z">
                                        </path>
                                        <path
                                            d="M13.6661 7.29147H0.334644C0.173562 7.29147 0.0429688 7.16088 0.0429688 6.99979C0.0429688 6.83871 0.173562 6.70812 0.334644 6.70812H13.6661C13.8272 6.70812 13.9578 6.83868 13.9578 6.99979C13.9578 7.16088 13.8272 7.29147 13.6661 7.29147ZM12.7022 3.81187H1.29862C1.13754 3.81187 1.00695 3.6813 1.00695 3.52019C1.00695 3.35908 1.13751 3.22852 1.29862 3.22852H12.7022C12.8633 3.22852 12.9939 3.35908 12.9939 3.52019C12.9939 3.6813 12.8632 3.81187 12.7022 3.81187ZM12.7022 10.771H1.29862C1.13754 10.771 1.00695 10.6404 1.00695 10.4794C1.00695 10.3183 1.13751 10.1877 1.29862 10.1877H12.7022C12.8633 10.1877 12.9939 10.3183 12.9939 10.4794C12.9939 10.6404 12.8632 10.771 12.7022 10.771Z">
                                        </path>
                                    </g>
                                </svg>
                                <span>EN</span>
                            </div>
                            <i class="bi bi-caret-down-fill"></i>
                        </div>
                        <ul class="language-list">
                            <li><a href="#">English</a></li>
                        </ul>
                    </div>
                    <a class="primary-btn2 btn-hover d-md-none d-flex " href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">
                        <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M6.94519 6.64398L1.42283 5.13832C0.702381 4.94189 0.68943 4.60244 1.40232 4.3774L12.3969 0.905214C12.7401 0.796742 12.93 0.989941 12.8221 1.33101L9.35047 12.325C9.12651 13.0341 8.78706 13.0298 8.58954 12.3045L7.08334 6.78267C7.07314 6.75008 7.0552 6.72043 7.03104 6.69628C7.00689 6.67213 6.97779 6.65418 6.94519 6.64398Z" />
                        </svg>
                        Chat on WhatsApp
                        <span></span>
                    </a>
                </div>
            </div>
            <div class="nav-right">
                <div class="contact-area">
                    <div class="search-bar">
                        <div class="search-btn">
                            <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                <g>
                                    <path
                                        d="M15.7417 14.6098L13.486 12.3621C14.7088 10.8514 15.3054 8.9291 15.1526 6.99153C14.9998 5.05396 14.1093 3.24888 12.6648 1.94851C11.2203 0.648146 9.33193 -0.0483622 7.38901 0.00261294C5.44609 0.0535881 3.59681 0.84816 2.22248 2.22248C0.84816 3.59681 0.0535881 5.44609 0.00261294 7.38901C-0.0483622 9.33193 0.648146 11.2203 1.94851 12.6648C3.24888 14.1093 5.05396 14.9998 6.99153 15.1526C8.9291 15.3054 10.8514 14.7088 12.3621 13.486L14.6098 15.7417C14.6839 15.8164 14.7721 15.8757 14.8692 15.9161C14.9664 15.9566 15.0705 15.9774 15.1758 15.9774C15.281 15.9774 15.3852 15.9566 15.4823 15.9161C15.5794 15.8757 15.6676 15.8164 15.7417 15.7417C15.8164 15.6676 15.8757 15.5794 15.9161 15.4823C15.9566 15.3852 15.9774 15.281 15.9774 15.1758C15.9774 15.0705 15.9566 14.9664 15.9161 14.8692C15.8757 14.7721 15.8164 14.6839 15.7417 14.6098ZM1.62572 7.60368C1.62572 6.42135 1.97632 5.26557 2.63319 4.2825C3.29005 3.29943 4.22368 2.53322 5.31601 2.08076C6.40834 1.62831 7.61031 1.50992 8.76992 1.74058C9.92953 1.97124 10.9947 2.54059 11.8307 3.37662C12.6668 4.21266 13.2361 5.27783 13.4668 6.43744C13.6974 7.59705 13.579 8.79902 13.1266 9.89134C12.6741 10.9837 11.9079 11.9173 10.9249 12.5742C9.94178 13.231 8.78601 13.5816 7.60368 13.5816C6.01822 13.5816 4.49771 12.9518 3.37662 11.8307C2.25554 10.7096 1.62572 9.18913 1.62572 7.60368Z" />
                                </g>
                            </svg>
                        </div>
                    </div>
                    <a href="<?= e(url(App\CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php')) ?>" class="login-btn" aria-label="Customer account">
                        <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M9.10982 6.49154C10.0056 5.83822 10.589 4.78089 10.589 3.58974C10.589 1.61036 8.97862 0 6.99924 0C5.01985 0 3.4095 1.61036 3.4095 3.58974C3.4095 4.78089 3.99282 5.83822 4.88866 6.49154C2.66178 7.34371 1.07617 9.5028 1.07617 12.0256C1.07617 13.1143 1.96186 14 3.05053 14H10.9479C12.0366 14 12.9223 13.1143 12.9223 12.0256C12.9223 9.5028 11.3367 7.34371 9.10982 6.49154ZM4.48643 3.58974C4.48643 2.20418 5.61368 1.07693 6.99924 1.07693C8.3848 1.07693 9.51205 2.20418 9.51205 3.58974C9.51205 4.9753 8.3848 6.10258 6.99924 6.10258C5.61368 6.10258 4.48643 4.9753 4.48643 3.58974ZM10.9479 12.9231H3.05053C2.55569 12.9231 2.15311 12.5205 2.15311 12.0256C2.15311 9.35342 4.32704 7.17946 6.99927 7.17946C9.67149 7.17946 11.8454 9.35339 11.8454 12.0256C11.8454 12.5205 11.4428 12.9231 10.9479 12.9231Z" />
                        </svg>
                    </a>
                    <div class="language-area">
                        <div class="language-btn">
                            <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                <g>
                                    <path
                                        d="M7 14C5.13023 14 3.37239 13.2719 2.05023 11.9498C0.728137 10.6276 0 8.86977 0 7C0 5.13023 0.728137 3.37239 2.05023 2.05023C3.37239 0.728137 5.13023 0 7 0C8.86977 0 10.6276 0.728137 11.9498 2.05023C13.2719 3.37239 14 5.13023 14 7C14 8.86977 13.2719 10.6276 11.9498 11.9498C10.6276 13.2719 8.86977 14 7 14ZM7 0.583324C3.46183 0.583324 0.583324 3.46183 0.583324 7C0.583324 10.5382 3.46183 13.4166 7 13.4166C10.5382 13.4166 13.4166 10.5382 13.4166 7C13.4166 3.46183 10.5382 0.583324 7 0.583324Z" />
                                    <path
                                        d="M7 14C5.90297 14 4.8854 13.2486 4.13468 11.8841C3.41431 10.5747 3.01758 8.84018 3.01758 7C3.01758 5.15982 3.41431 3.42527 4.13468 2.11589C4.8854 0.751433 5.90297 0 7 0C8.09704 0 9.11461 0.751433 9.8653 2.11589C10.5857 3.42527 10.9824 5.15982 10.9824 7C10.9824 8.84018 10.5857 10.5747 9.8653 11.8841C9.11461 13.2486 8.09704 14 7 14ZM7 0.583324C6.12536 0.583324 5.2893 1.22746 4.64579 2.39709C3.97198 3.62179 3.6009 5.25645 3.6009 7C3.6009 8.74355 3.97198 10.3782 4.64576 11.6029C5.28927 12.7725 6.12533 13.4166 6.99998 13.4166C7.87462 13.4166 8.71068 12.7725 9.35419 11.6029C10.028 10.3782 10.3991 8.74355 10.3991 7C10.3991 5.25645 10.028 3.62179 9.35419 2.39709C8.71071 1.22746 7.87462 0.583324 7 0.583324Z" />
                                    <path
                                        d="M6.99968 13.9573C6.8386 13.9573 6.70801 13.8267 6.70801 13.6657V0.334156C6.70801 0.173074 6.83857 0.0424805 6.99968 0.0424805C7.16077 0.0424805 7.29136 0.173074 7.29136 0.334156V13.6657C7.29136 13.8267 7.16077 13.9573 6.99968 13.9573Z" />
                                    <path
                                        d="M13.6661 7.29147H0.334644C0.173562 7.29147 0.0429688 7.16088 0.0429688 6.99979C0.0429688 6.83871 0.173562 6.70812 0.334644 6.70812H13.6661C13.8272 6.70812 13.9578 6.83868 13.9578 6.99979C13.9578 7.16088 13.8272 7.29147 13.6661 7.29147ZM12.7022 3.81187H1.29862C1.13754 3.81187 1.00695 3.6813 1.00695 3.52019C1.00695 3.35908 1.13751 3.22852 1.29862 3.22852H12.7022C12.8633 3.22852 12.9939 3.35908 12.9939 3.52019C12.9939 3.6813 12.8632 3.81187 12.7022 3.81187ZM12.7022 10.771H1.29862C1.13754 10.771 1.00695 10.6404 1.00695 10.4794C1.00695 10.3183 1.13751 10.1877 1.29862 10.1877H12.7022C12.8633 10.1877 12.9939 10.3183 12.9939 10.4794C12.9939 10.6404 12.8632 10.771 12.7022 10.771Z" />
                                </g>
                            </svg>
                            <span>EN</span>
                            <i class="bi bi-caret-down-fill"></i>
                        </div>
                        <ul class="language-list">
                            <li><a href="#">English</a></li>
                        </ul>
                    </div>
                </div>
                <div class="sidebar-button mobile-menu-btn">
                    <svg width="20" height="18" viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M1.29445 2.8421H10.5237C11.2389 2.8421 11.8182 2.2062 11.8182 1.42105C11.8182 0.635903 11.2389 0 10.5237 0H1.29445C0.579249 0 0 0.635903 0 1.42105C0 2.2062 0.579249 2.8421 1.29445 2.8421Z">
                        </path>
                        <path
                            d="M1.23002 10.421H18.77C19.4496 10.421 20 9.78506 20 8.99991C20 8.21476 19.4496 7.57886 18.77 7.57886H1.23002C0.550421 7.57886 0 8.21476 0 8.99991C0 9.78506 0.550421 10.421 1.23002 10.421Z">
                        </path>
                        <path
                            d="M18.8052 15.1579H10.2858C9.62563 15.1579 9.09094 15.7938 9.09094 16.5789C9.09094 17.3641 9.62563 18 10.2858 18H18.8052C19.4653 18 20 17.3641 20 16.5789C20 15.7938 19.4653 15.1579 18.8052 15.1579Z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </header>
    <!-- header Section End-->
    <!-- Topbar Area Start -->
    <!-- ===============================
     EASYWAY LOGISTICS - TOPBAR SECTION
================================== -->
    <div class="home4-topbar-area">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <!-- Announcement Slider -->
                <div class="col-xl-5 col-lg-7 col-md-8">
                    <div class="slider-area">
                        <div class="slider-btn-grp">
                            <div class="slider-btn topbar-slider-prev">
                                <svg width="8" height="8" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M6.40018 8C6.40018 6.11765 2.95214 4.62745 2.00018 4C3.12523 3.37255 6.40018 1.88235 6.40018 0"
                                        stroke-width="2" />
                                </svg>
                            </div>
                            <div class="slider-btn topbar-slider-next">
                                <svg width="8" height="8" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M2.00018 8C2.00018 6.11765 5.44822 4.62745 6.40018 4C5.27513 3.37255 2.00018 1.88235 2.00018 0"
                                        stroke-width="2" />
                                </svg>
                            </div>
                        </div>

                        <!-- Swiper Slider -->
                        <div class="swiper home3-topbar-slider">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="single-slide">
                                        <p>Request a tailored quote for <strong>international shipments</strong>.</p>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="single-slide">
                                        <p>Clear booking references and <strong>visible delivery milestones</strong>.</p>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="single-slide">
                                        <p>International delivery enquiries to <strong>supported destinations</strong>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="col-lg-3 col-md-4 d-flex align-items-center justify-content-end">
                    <a class="primary-btn2 d-md-flex d-none btn-hover" href="<?= e(url(App\CustomerAuth::check() ? 'customer/book.php' : 'customer/register.php')) ?>">
                        <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M6.94519 6.64398L1.42283 5.13832C0.702381 4.94189 0.68943 4.60244 1.40232 4.3774L12.3969 0.905214C12.7401 0.796742 12.93 0.989941 12.8221 1.33101L9.35047 12.325C9.12651 13.0341 8.78706 13.0298 8.58954 12.3045L7.08334 6.78267C7.07314 6.75008 7.0552 6.72043 7.03104 6.69628C7.00689 6.67213 6.97779 6.65418 6.94519 6.64398Z" />
                        </svg>
                        Book with Easyway
                        <span></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?= flash_markup() ?>

    <!-- ===============================
     EASYWAY LOGISTICS - HERO SECTION
================================== -->
    <section id="home">
        <div class="home4-banner-section">
            <div class="container">
                <div class="row align-items-center justify-content-between">
                    <!-- Text Content -->
                    <div class="col-xl-5 col-lg-6">
                        <div class="banner-content-wrapper">
                            <div class="banner-content">
                                <h1 class="wow fadeInUp" data-wow-delay="0.2s">
                                    Fast. Reliable. Global Courier Service.
                                </h1>
                                <p class="wow fadeInUp" data-wow-delay="0.4s">
                                    Delivering parcels across Nigeria and beyond with clear support from booking to
                                    final delivery.
                                </p>
                            </div>

                            <!-- Decorative Line -->
                            <svg height="6" viewBox="0 0 536 6" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M5 2.5L0 0.113249V5.88675L5 3.5V2.5ZM531 3.5L536 5.88675V0.113249L531 2.5V3.5ZM4.5 3V3.5H531.5V3V2.5H4.5V3Z"
                                    fill-opacity="0.15" />
                            </svg>

                            <div class="trustpilot-rating-area wow fadeInUp" data-wow-delay="0.6s">
                                <strong><i class="bi bi-shield-check"></i></strong>
                                <div class="trustpilot-rating">
                                    <div class="rating-area">
                                        <span>Secure references and verified tracking milestones</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Image -->
                    <div class="col-xl-6 col-lg-6 col-md-8">
                        <div class="banner-img-area text-center wow fadeInRight" data-wow-delay="0.5s">
                            <img src="assets/img/easyway/delivery.png" alt="Easyway Logistics Delivery">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Background Shape -->
            <img class="shape" src="assets/img/home4/vector/banner-shape.png" alt="Decorative Shape">
        </div>
    </section>
    <!-- home4 Banner Section End-->

    <!-- ===============================
     EASYWAY LOGISTICS - TRACKING & CONTACT SECTION
================================== -->
    <section id="track">
        <div class="home2-contact-info three">
            <div class="container">
                <div class="row gy-4 align-items-center justify-content-between">
                    <!-- Tracking Area -->
                    <div class="col-xl-5 col-lg-7 col-md-7">
                        <div class="contact-tracking-area">
                            <h5>Track Your Parcel</h5>
                            <form class="email-area" action="<?= e(url('tracking.php')) ?>" method="get">
                                <div class="form-inner">
                                    <input type="text" name="tracking_id" placeholder="Enter Your Tracking Number"
                                        maxlength="19" autocomplete="off" data-tracking-input required>
                                    <button class="primary-btn2 two black-bg btn-hover" type="submit">
                                        Track & Trace
                                        <svg width="10" height="10" viewBox="0 0 10 10"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M5.83333 4.16667V0H4.16667V4.16667H0V5.83333H4.16667V10H5.83333V5.83333H10V4.16667H5.83333Z" />
                                        </svg>
                                        <span></span>
                                    </button>
                                </div>
                            </form>
                            <p>
                                Having trouble tracking your parcel?
                                <a href="<?= e(url('contact.php')) ?>">Contact our support team</a> for quick assistance.
                            </p>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-xl-6 col-lg-4 col-md-5">
                        <ul class="contact-list">
                            <!-- Email -->
                            <li class="single-contact">
                                <div class="icon">
                                    <svg width="18" height="13" viewBox="0 0 18 13" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M11.6632 7.3252L10.1556 8.83783C9.5443 9.4513 8.46866 9.46452 7.84411 8.83783L6.3365 7.3252L0.921875 12.7574C1.12343 12.8506 1.34565 12.9062 1.5819 12.9062H16.4178C16.6541 12.9062 16.8762 12.8507 17.0777 12.7574L11.6632 7.3252Z" />
                                        <path
                                            d="M16.418 0.25H1.58203C1.34578 0.25 1.12356 0.305617 0.922078 0.398816L6.70799 6.20392L8.59099 8.09322C8.79082 8.29305 9.20925 8.29305 9.40908 8.09322L11.2921 6.20392L17.0779 0.398781C16.8764 0.305547 16.6542 0.25 16.418 0.25ZM0.168258 1.13636V11.3242C0.168258 11.575 0.232172 11.809 0.33648 12.0199L5.76048 6.5783L0.168258 1.13636ZM17.8317 1.13629L12.4078 6.5783L17.8317 12.02C17.936 11.8091 18 11.575 18 11.3242V1.83203C18 1.58123 17.936 1.34716 17.8317 1.13629Z" />
                                    </svg>
                                </div>
                                <div class="contact-content">
                                    <p>Send Your Documents via Email</p>
                                    <a href="mailto:<?= e(support_email()) ?>"><?= e(support_email()) ?></a>
                                </div>
                            </li>

                            <!-- Phone -->
                            <li class="single-contact">
                                <div class="icon">
                                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16.5556 11.8149C15.4536 11.8149 14.3716 11.6425 13.3462 11.3037C12.8437 11.1323 12.226 11.2895 11.9194 11.6045L9.89547 13.1323C7.54831 11.8794 6.1025 10.4341 4.86669 8.10452L6.34958 6.13334C6.73484 5.74859 6.87303 5.18656 6.70747 4.65922C6.36716 3.62844 6.19428 2.5469 6.19428 1.4444C6.19433 0.647951 5.54638 0 4.74997 0H1.44436C0.647951 0 0 0.647951 0 1.44436C0 10.5732 7.42676 17.9999 16.5556 17.9999C17.352 17.9999 18 17.352 18 16.5556V13.2592C18 12.4629 17.352 11.8149 16.5556 11.8149Z" />
                                    </svg>
                                </div>
                                <div class="contact-content">
                                    <p>24/7 Customer Support</p>
                                    <?php foreach (support_phones() as $phone): ?><a class="d-block" href="tel:<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a><?php endforeach; ?>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Decorative Shapes -->
            <img class="img-1" src="assets/img/home4/vector/home4-contact-info-shape1.png" alt="Decorative Shape 1">
            <img class="img-2" src="assets/img/home4/vector/home4-contact-info-shape2.png" alt="Decorative Shape 2">
        </div>
    </section>
    <!-- ===============================
     END TRACKING & CONTACT SECTION
================================== -->
    <section id="about">
        <!-- Home4 Counter Section Start -->
        <div class="home4-counter-section mb-120">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-9 col-lg-10">
                        <div class="section-title text-center mb-120">
                            <h2>We are dedicated to delivering outstanding courier and logistics services tailored to
                                the
                                unique needs of both businesses and individuals.</h2>
                        </div>
                    </div>
                </div>
                <div class="row gy-4 align-items-center justify-content-center">
                    <div class="col-lg-4 col-md-6 col-sm-6 wow animate fadeInDown" data-wow-delay="200ms"
                        data-wow-duration="1500ms">
                        <div class="counter-card">
                            <div class="icon">
                                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 0H14V14L9.33333 4.66667L0 0Z" />
                                </svg>
                            </div>
                            <div class="counter-content">
                                <h2>
                                    <span class="counter">8</span>+
                                </h2>
                                <p> Years of Experienece </p>
                            </div>
                            <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M52.1099 20.3492C52.6814 21.6565 53.135 23.0122 53.4654 24.4002L58.125 26.1938V33.8063L53.4642 35.6003C52.8728 38.0831 51.8892 40.4558 50.5505 42.6288L52.5789 47.1961L47.196 52.5791L42.6302 50.5512C40.4567 51.8904 38.0834 52.8742 35.5998 53.4655L33.8064 58.125H26.1939L24.3998 53.4642C21.917 52.8728 19.5443 51.8892 17.3713 50.5505L12.8039 52.5789L7.42113 47.1961L9.44906 42.6304C8.10992 40.4568 7.12611 38.0835 6.53473 35.5999L1.875 33.8063V26.1938L6.53531 24.3999C6.86565 23.0121 7.31925 21.6566 7.8907 20.3495L12.5231 23.1963C11.7014 25.3056 11.25 27.6 11.25 30.0001C11.25 40.3563 19.6438 48.75 30 48.75C40.3562 48.75 48.75 40.3563 48.75 30.0001C48.75 27.6 48.2987 25.3055 47.4768 23.1965L52.1099 20.3492ZM10.4497 10.0498C10.1975 9.93634 9.93645 10.1974 10.0499 10.4496L11.7273 14.1803C11.7777 14.2924 11.7581 14.4164 11.6755 14.5075L8.92734 17.5372C8.74043 17.7433 8.90754 18.0713 9.1841 18.0411L13.2505 17.5988C13.3091 17.5917 13.3685 17.6022 13.4211 17.629C13.4736 17.6558 13.517 17.6976 13.5457 17.7492L15.578 21.2991C15.7163 21.5405 16.0796 21.4829 16.1365 21.2106L16.9723 17.2065C16.9836 17.1486 17.0119 17.0954 17.0537 17.0537C17.0954 17.012 17.1486 16.9836 17.2065 16.9723L21.2107 16.1365C21.483 16.0797 21.5406 15.7161 21.2992 15.578L17.7493 13.5457C17.6978 13.517 17.6559 13.4736 17.6291 13.421C17.6024 13.3685 17.5919 13.3091 17.599 13.2505L18.0414 9.18412C18.0715 8.90755 17.7435 8.74044 17.5375 8.92736L14.5077 11.6754C14.4645 11.7156 14.4102 11.742 14.352 11.7512C14.2937 11.7605 14.2339 11.7522 14.1804 11.7273L10.4497 10.0498ZM49.9502 10.4497C50.0637 10.1976 49.8026 9.93646 49.5504 10.0499L45.8197 11.7273C45.7662 11.7522 45.7065 11.7605 45.6482 11.7513C45.5899 11.742 45.5357 11.7156 45.4925 11.6754L42.4628 8.92736C42.2568 8.74044 41.9288 8.90755 41.9589 9.18412L42.4012 13.2505C42.4084 13.3091 42.3979 13.3685 42.3711 13.421C42.3443 13.4736 42.3025 13.517 42.2509 13.5457L38.7011 15.578C38.4596 15.7163 38.5172 16.0797 38.7895 16.1365L42.7936 16.9723C42.8515 16.9836 42.9047 17.012 42.9464 17.0537C42.9882 17.0954 43.0165 17.1486 43.0279 17.2065L43.8637 21.2107C43.9206 21.4831 44.2841 21.5406 44.4223 21.2992L46.4544 17.7493C46.4831 17.6978 46.5265 17.6559 46.5791 17.6291C46.6317 17.6023 46.691 17.5918 46.7496 17.5989L50.816 18.0413C51.0926 18.0714 51.2597 17.7434 51.0728 17.5374L48.3247 14.5076C48.2846 14.4643 48.2582 14.4101 48.2489 14.3518C48.2397 14.2936 48.248 14.2338 48.2728 14.1803L49.9502 10.4497ZM30.2828 2.06896L31.7346 5.89302C31.7549 5.94845 31.7912 5.99658 31.8389 6.03127C31.8866 6.06597 31.9436 6.08564 32.0026 6.08779L36.0881 6.28689C36.366 6.30037 36.4798 6.65052 36.2629 6.82478L33.0746 9.38732C33.0282 9.42372 32.9936 9.47314 32.9754 9.52926C32.9572 9.58539 32.9561 9.64568 32.9722 9.70244L34.0453 13.6495C34.1183 13.918 33.8205 14.1343 33.5878 13.982L30.1655 11.7416C30.1165 11.7087 30.0588 11.6911 29.9998 11.6911C29.9408 11.6911 29.8831 11.7087 29.8342 11.7416L26.4118 13.982C26.1791 14.1343 25.8813 13.918 25.9543 13.6497L27.0274 9.70255C27.0436 9.6458 27.0425 9.58551 27.0242 9.52938C27.006 9.47326 26.9715 9.42384 26.925 9.38744L23.7368 6.8249C23.52 6.65064 23.6337 6.3006 23.9115 6.28701L27.9971 6.08791C28.0561 6.08576 28.1131 6.06608 28.1609 6.03139C28.2086 5.9967 28.2449 5.94857 28.2652 5.89314L29.717 2.06908C29.8153 1.81033 30.1847 1.81033 30.2828 2.06896ZM30 16.7721C26.8985 16.7721 24.3844 19.2864 24.3844 22.3877C24.3844 25.4891 26.8986 28.0034 30 28.0034C33.1014 28.0034 35.6156 25.4891 35.6156 22.3877C35.6156 19.2864 33.1015 16.7721 30 16.7721ZM20.2008 39.0871L39.7991 39.087C40.1311 39.087 40.4168 38.9494 40.6237 38.6897C40.8307 38.4301 40.9012 38.121 40.8273 37.7973C39.6988 32.8595 35.2798 29.1752 30 29.1752C24.7201 29.1752 20.3011 32.8595 19.1726 37.7973C19.0986 38.121 19.1691 38.4301 19.376 38.6897C19.5831 38.9494 19.8689 39.0871 20.2008 39.0871Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 d-flex justify-content-lg-center wow animate fadeInUp"
                        data-wow-delay="200ms" data-wow-duration="1500ms">
                        <div class="counter-card two">
                            <div class="icon">
                                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 0H14V14L9.33333 4.66667L0 0Z" />
                                </svg>
                            </div>
                            <div class="counter-content">
                                <h2>
                                    <span class="counter">18</span>+
                                </h2>
                                <p>Countries Available</p>
                            </div>
                            <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M56.0246 40.7624C54.5454 44.312 52.3598 47.5237 49.6007 50.2023C46.8415 52.8808 43.5665 54.9703 39.9746 56.3437C42.8059 52.6499 45.0559 47.3062 46.2559 40.8562C46.2746 40.8187 46.2746 40.7999 46.2746 40.7624H56.0246ZM42.3184 40.7624C40.4809 49.7812 36.4309 56.3624 31.9309 57.8624V40.7624H42.3184ZM28.0684 40.7624V57.8624C23.5684 56.3624 19.4996 49.7812 17.6809 40.7624H28.0684ZM20.0246 56.3249C16.4328 54.9516 13.1577 52.8621 10.3986 50.1835C7.63943 47.505 5.45382 44.2933 3.97461 40.7437H13.7246C13.7246 40.7437 13.7246 40.7812 13.7434 40.7999C14.9434 47.2687 17.1934 52.6124 20.0246 56.3062V56.3249ZM12.7496 29.9999C12.7496 32.3437 12.8996 34.6499 13.1621 36.8812H2.68086C2.11836 34.6499 1.81836 32.3437 1.81836 29.9999C1.81836 27.6562 2.11836 25.3499 2.68086 23.1187H13.1621C12.8809 25.3499 12.7496 27.6562 12.7496 29.9999ZM28.0684 23.1187V36.8812H17.0996C16.8184 34.6499 16.6309 32.3624 16.6309 29.9999C16.6309 27.6374 16.8184 25.3499 17.0996 23.1187H28.0684ZM43.3871 29.9999C43.3871 32.3624 43.1996 34.6499 42.9184 36.8812H31.9496V23.1187H42.9184C43.1996 25.3499 43.3871 27.6374 43.3871 29.9999ZM58.1809 29.9999C58.1809 32.3437 57.8809 34.6499 57.3184 36.8812H46.8371C47.1184 34.6499 47.2496 32.3437 47.2496 29.9999C47.2496 27.6562 47.0996 25.3499 46.8371 23.1187H57.3184C57.8809 25.3499 58.1809 27.6562 58.1809 29.9999ZM56.0246 19.2374H46.2746C46.2746 19.2374 46.2746 19.1999 46.2559 19.1812C45.0559 12.7124 42.8059 7.3687 39.9746 3.67495C47.1184 6.41245 53.0059 11.9812 56.0246 19.2562V19.2374ZM42.3184 19.2374H31.9309V2.13745C36.4309 3.63745 40.4996 10.2187 42.3184 19.2374ZM28.0684 2.13745V19.2374H17.6809C19.5184 10.2187 23.5684 3.63745 28.0684 2.13745ZM20.0246 3.67495C17.1934 7.3687 14.9434 12.7124 13.7434 19.1624C13.7246 19.1999 13.7246 19.2187 13.7246 19.2562H3.97461C6.97461 11.9812 12.8809 6.41245 20.0246 3.67495Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 d-flex justify-content-lg-end wow animate fadeInDown"
                        data-wow-delay="200ms" data-wow-duration="1500ms">
                        <div class="counter-card three">
                            <div class="icon">
                                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 0H14V14L9.33333 4.66667L0 0Z" />
                                </svg>
                            </div>
                            <div class="counter-content">
                                <h2>
                                    <span class="counter">26</span>k+
                                </h2>
                                <p>Packages Delivered</p>
                            </div>
                            <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M44.4451 40.8454C44.4997 40.8454 44.5633 40.8272 44.6088 40.7999L44.9815 40.5818C45.1361 40.4909 45.1906 40.2909 45.0997 40.1272C45.0545 40.0519 44.9815 39.9974 44.8966 39.9753C44.8116 39.9532 44.7213 39.9653 44.6451 40.009L44.2724 40.2272C44.1179 40.3181 44.0633 40.5181 44.1542 40.6818C44.227 40.7818 44.3361 40.8454 44.4451 40.8454ZM42.0906 41.0818C42.1451 41.0818 42.2088 41.0636 42.2542 41.0363L43.027 40.5909C43.1815 40.4999 43.2361 40.2999 43.1451 40.1363C43.0999 40.061 43.027 40.0065 42.942 39.9844C42.857 39.9623 42.7668 39.9744 42.6906 40.0181L41.9179 40.4636C41.7633 40.5545 41.7088 40.7545 41.7997 40.9181C41.8633 41.0181 41.9724 41.0818 42.0906 41.0818ZM43.6088 40.6181L41.927 41.5999C41.7724 41.6909 41.7179 41.8909 41.8088 42.0545C41.8724 42.1636 41.9815 42.2181 42.0997 42.2181C42.1542 42.2181 42.2179 42.1999 42.2633 42.1727L43.9451 41.1909C44.0997 41.0999 44.1542 40.8999 44.0633 40.7363C44.0178 40.6614 43.9449 40.6072 43.86 40.5851C43.7752 40.563 43.685 40.5749 43.6088 40.6181ZM35.4815 39.2999L39.4179 41.5727C39.4724 41.5999 39.527 41.6181 39.5815 41.6181C39.6997 41.6181 39.8088 41.5545 39.8724 41.4545C39.9633 41.2999 39.9088 41.0909 39.7542 40.9999L35.8179 38.7272C35.6633 38.6363 35.4542 38.6909 35.3633 38.8454C35.2724 38.9999 35.327 39.209 35.4815 39.2999ZM35.4815 41.7999L39.4179 44.0727C39.4724 44.0999 39.527 44.1181 39.5815 44.1181C39.6997 44.1181 39.8088 44.0545 39.8724 43.9545C39.9633 43.7999 39.9088 43.5909 39.7542 43.4999L35.8179 41.2272C35.6633 41.1363 35.4542 41.1909 35.3633 41.3454C35.2724 41.4999 35.327 41.709 35.4815 41.7999ZM35.4815 44.2999L39.4179 46.5727C39.4724 46.5999 39.527 46.6181 39.5815 46.6181C39.6997 46.6181 39.8088 46.5545 39.8724 46.4545C39.9633 46.2999 39.9088 46.0909 39.7542 45.9999L35.8179 43.7272C35.6633 43.6363 35.4542 43.6909 35.3633 43.8454C35.2724 43.9999 35.327 44.1999 35.4815 44.2999ZM35.4815 46.7999L39.4179 49.0727C39.4724 49.0999 39.527 49.1181 39.5815 49.1181C39.6997 49.1181 39.8088 49.0545 39.8724 48.9545C39.9633 48.7999 39.9088 48.5909 39.7542 48.4999L35.8179 46.2272C35.6633 46.1363 35.4542 46.1909 35.3633 46.3454C35.2724 46.4999 35.327 46.6999 35.4815 46.7999ZM35.4815 49.2999L39.4179 51.5727C39.4724 51.5999 39.527 51.6181 39.5815 51.6181C39.6997 51.6181 39.8088 51.5545 39.8724 51.4545C39.9633 51.2999 39.9088 51.0909 39.7542 50.9999L35.8179 48.7272C35.6633 48.6363 35.4542 48.6909 35.3633 48.8454C35.2724 48.9999 35.327 49.1999 35.4815 49.2999Z" />
                                <path
                                    d="M58.4724 29.0727L52.9996 25.9091L53.0087 22.7909V22.7818C53.0087 22.7727 52.9996 22.7636 52.9996 22.7545C52.9996 22.7091 52.9815 22.6727 52.9633 22.6363C52.9542 22.6181 52.9451 22.6091 52.936 22.5909C52.9178 22.5636 52.8906 22.5454 52.8633 22.5181L52.836 22.4909L21.9087 4.64542C21.8582 4.61419 21.8 4.59766 21.7406 4.59766C21.6812 4.59766 21.6229 4.61419 21.5724 4.64542L1.58146 16.2636C1.57237 16.2727 1.57237 16.2818 1.56328 16.2818C1.53601 16.3 1.50874 16.3272 1.48146 16.3636C1.43831 16.4141 1.41561 16.479 1.41783 16.5454L1.36328 35.5909C1.36328 35.7091 1.42692 35.8181 1.52692 35.8818L32.4542 53.7363C32.5087 53.7636 32.5633 53.7818 32.6178 53.7818C32.6724 53.7818 32.7269 53.7636 32.7815 53.7363L35.4815 52.1727L40.9996 55.3545C41.0542 55.3818 41.1087 55.4 41.1633 55.4C41.2178 55.4 41.2815 55.3818 41.3269 55.3545C41.4269 55.2909 41.4906 55.1818 41.4906 55.0636V48.6727L52.5997 42.2181L58.1178 45.4C58.1724 45.4272 58.2269 45.4454 58.2815 45.4454C58.336 45.4454 58.3997 45.4272 58.4451 45.4C58.5451 45.3363 58.6087 45.2272 58.6087 45.1091V29.3636C58.6205 29.3069 58.6135 29.2479 58.5889 29.1955C58.5644 29.143 58.5235 29.1 58.4724 29.0727ZM32.2996 52.8727L2.02692 35.4L2.08146 17.1272L32.3451 34.6L32.2996 52.8727ZM32.6815 34.0181L2.40874 16.5454L21.7451 5.31814L52.0087 22.7909L32.6815 34.0181ZM32.9633 52.8727L33.0178 34.6L52.3451 23.3727L52.336 25.5272L51.4087 24.9909C51.3614 24.965 51.308 24.9525 51.2542 24.9545H51.236C51.2269 24.9545 51.2178 24.9636 51.2087 24.9636H51.1542L51.0996 24.9909C51.0906 24.9909 51.0815 24.9909 51.0724 25L37.4633 32.9L33.9451 34.9454C33.9451 34.9454 33.9451 34.9545 33.936 34.9545C33.8906 34.9818 33.8542 35.0181 33.8269 35.0636C33.7996 35.1091 33.7906 35.1636 33.7906 35.2181V35.2363L33.7451 50.9636C33.7451 50.9818 33.7542 50.9909 33.7542 51.0091C33.7542 51.0272 33.7633 51.0545 33.7633 51.0727C33.7633 51.0818 33.7633 51.0909 33.7724 51.1C33.7815 51.1181 33.7906 51.1272 33.7996 51.1363C33.8087 51.1545 33.8178 51.1636 33.8269 51.1818C33.8451 51.2 33.8633 51.2181 33.8906 51.2363L33.9087 51.2545L34.836 51.7909L32.9633 52.8727ZM43.9087 36.7C43.9633 36.7272 44.0178 36.7454 44.0724 36.7454C44.1269 36.7454 44.1906 36.7272 44.236 36.7L45.8724 35.7545C45.9724 35.6909 46.036 35.5818 46.036 35.4636V32.7272L48.2815 31.4272V39.2636L41.4996 43.2V35.3636L43.7451 34.0636V36.409C43.7451 36.5272 43.8087 36.6363 43.9087 36.7ZM42.8906 30.5181L45.136 29.2091L47.9542 30.8545L45.7087 32.1636L42.8906 30.5181ZM48.6087 39.8454L50.536 41.0091L41.4996 46.2545V43.9727L48.6087 39.8454ZM40.8451 43.7818V46.7C40.7906 46.8 40.7906 46.9181 40.8451 47.0181V54.4909L34.3996 50.7727L34.4451 35.809L40.8451 39.509V43.7818ZM40.8451 38.7363L34.7724 35.2363L37.7178 33.5272L40.8451 35.3727V38.7363ZM41.1724 34.7909L38.3724 33.1363L40.6087 31.8363L43.4178 33.4818L41.1724 34.7909ZM41.5087 47.909V47.0272L51.1996 41.4L51.9633 41.8363L41.5087 47.909ZM57.9724 44.5454L51.5269 40.8272L51.5724 25.8636L52.4178 26.3545L52.4996 26.4L57.9724 29.5636V44.5454Z" />
                                <path
                                    d="M52.6084 29.3453L56.5448 31.618C56.5994 31.6453 56.6539 31.6634 56.7084 31.6634C56.8266 31.6634 56.9357 31.5998 56.9994 31.4998C57.0903 31.3453 57.0357 31.1362 56.8812 31.0453L52.9448 28.7725C52.7903 28.6816 52.5812 28.7362 52.4903 28.8907C52.3994 29.0544 52.4539 29.2544 52.6084 29.3453ZM56.8812 33.5453L52.9448 31.2725C52.7903 31.1816 52.5812 31.2362 52.4903 31.3907C52.3994 31.5453 52.4539 31.7544 52.6084 31.8453L56.5448 34.118C56.5994 34.1453 56.6539 34.1634 56.7084 34.1634C56.8266 34.1634 56.9357 34.0998 56.9994 33.9998C57.0903 33.8453 57.0357 33.6362 56.8812 33.5453ZM56.8812 36.0453L52.9448 33.7725C52.7903 33.6816 52.5812 33.7362 52.4903 33.8907C52.3994 34.0453 52.4539 34.2544 52.6084 34.3453L56.5448 36.618C56.5994 36.6453 56.6539 36.6634 56.7084 36.6634C56.8266 36.6634 56.9357 36.5998 56.9994 36.4998C57.0903 36.3453 57.0357 36.1362 56.8812 36.0453ZM56.8812 38.5453L52.9448 36.2725C52.7903 36.1816 52.5812 36.2362 52.4903 36.3907C52.3994 36.5453 52.4539 36.7544 52.6084 36.8453L56.5448 39.118C56.5994 39.1453 56.6539 39.1634 56.7084 39.1634C56.8266 39.1634 56.9357 39.0998 56.9994 38.9998C57.0903 38.8453 57.0357 38.6362 56.8812 38.5453ZM56.8812 41.0453L52.9448 38.7725C52.7903 38.6816 52.5812 38.7362 52.4903 38.8907C52.3994 39.0453 52.4539 39.2544 52.6084 39.3453L56.5448 41.618C56.5994 41.6453 56.6539 41.6634 56.7084 41.6634C56.8266 41.6634 56.9357 41.5998 56.9994 41.4998C57.0903 41.3453 57.0357 41.1362 56.8812 41.0453ZM29.1994 48.2362C29.3812 48.2362 29.5266 48.0907 29.5266 47.9089V35.9544C29.5266 35.7725 29.3812 35.6271 29.1994 35.6271C29.0175 35.6271 28.8721 35.7725 28.8721 35.9544V47.9089C28.863 48.0907 29.0175 48.2362 29.1994 48.2362ZM25.1994 45.9271C25.3812 45.9271 25.5266 45.7816 25.5266 45.5998V33.6453C25.5266 33.4634 25.3812 33.318 25.1994 33.318C25.0175 33.318 24.8721 33.4634 24.8721 33.6453V45.5998C24.863 45.7816 25.0084 45.9271 25.1994 45.9271ZM21.1903 43.618C21.3721 43.618 21.5175 43.4725 21.5175 43.2907V31.3362C21.5175 31.1544 21.3721 31.0089 21.1903 31.0089C21.0084 31.0089 20.863 31.1544 20.863 31.3362V43.2907C20.863 43.4634 21.0084 43.618 21.1903 43.618ZM17.1903 41.2998C17.3721 41.2998 17.5175 41.1544 17.5175 40.9725V29.018C17.5175 28.8362 17.3721 28.6907 17.1903 28.6907C17.0084 28.6907 16.863 28.8362 16.863 29.018V40.9725C16.8539 41.1544 17.0084 41.2998 17.1903 41.2998ZM13.1812 38.9907C13.363 38.9907 13.5084 38.8453 13.5084 38.6634V26.7089C13.5084 26.5271 13.363 26.3816 13.1812 26.3816C12.9994 26.3816 12.8539 26.5271 12.8539 26.7089V38.6634C12.8539 38.8453 12.9994 38.9907 13.1812 38.9907ZM9.18117 36.6816C9.36299 36.6816 9.50844 36.5362 9.50844 36.3544V24.3998C9.50844 24.218 9.36299 24.0725 9.18117 24.0725C8.99935 24.0725 8.8539 24.218 8.8539 24.3998V36.3544C8.8539 36.5362 8.99935 36.6816 9.18117 36.6816ZM5.18117 34.3725C5.36299 34.3725 5.50844 34.2271 5.50844 34.0453V22.0907C5.50844 21.9089 5.36299 21.7634 5.18117 21.7634C4.99935 21.7634 4.8539 21.9089 4.8539 22.0907V34.0453C4.84481 34.218 4.99935 34.3725 5.18117 34.3725ZM48.0903 22.5089L21.9175 7.39981C21.867 7.36859 21.8088 7.35205 21.7494 7.35205C21.6899 7.35205 21.6317 7.36859 21.5812 7.39981L6.33572 16.2544C6.23572 16.318 6.17208 16.4271 6.17208 16.5453C6.17208 16.6634 6.23572 16.7725 6.33572 16.8362L32.5084 31.9453C32.563 31.9725 32.6175 31.9907 32.6721 31.9907C32.7266 31.9907 32.7903 31.9725 32.8357 31.9453L48.0903 23.0907C48.1903 23.0271 48.2539 22.918 48.2539 22.7998C48.2539 22.6816 48.1903 22.5725 48.0903 22.5089Z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Home4 Counter Section End -->
        <!-- Home4 Feature Card Section Start -->
        <div class="home4-feature-card-section mb-120">
            <div class="container">
                <div class="row justify-content-center wow animate fadeInDown" data-wow-delay="200ms"
                    data-wow-duration="1500ms">
                    <div class="col-xxl-6 col-xl-8 col-lg-7">
                        <div class="section-title text-center mb-60">
                            <h2><span>EASYWAY</span> – Why Choose Us</h2>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-8 col-lg-12 wow animate fadeInUp" data-wow-delay="200ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card one">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img1.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>E — Efficient</h3>
                                    <p>We ensure quick, optimized, and cost-effective delivery operations for every
                                        shipment.</p>
                                </div>
                            </div>
                            <div class="feature-img-wrap">
                                <img src="assets/img/easyway/efficient.jpg" alt="">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow animate fadeInUp" data-wow-delay="400ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card two">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img2.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>A — Accurate</h3>
                                    <p>Every delivery is tracked and verified in real-time to ensure precision and
                                        reliability.</p>
                                </div>
                            </div>
                            <img class="vector-img" src="assets/img/home4/vector/featurea-card-4-vector2.png" alt="">
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow animate fadeInUp" data-wow-delay="600ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card three">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img3.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>S — Secure</h3>
                                    <p>We prioritize the safety of your parcels with top-tier packaging and handling
                                        standards.</p>
                                </div>
                            </div>
                            <img class="vetor-img2" src="assets/img/home4/vector/featurea-card-4-vector.png" alt="">
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow animate fadeInDown" data-wow-delay="400ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card four">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img4.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>Y — Yield-Driven</h3>
                                    <p>We focus on results — timely deliveries, improved satisfaction, and greater value
                                        for
                                        clients.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow animate fadeInDown" data-wow-delay="200ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card five">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img5.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>W — Worldwide</h3>
                                    <p>Reliable domestic and international shipping, connecting Nigeria to the rest of
                                        the
                                        world.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow animate fadeInUp" data-wow-delay="400ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card two">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img2.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>A — Automated</h3>
                                    <p>Technology-powered tracking and logistics automation that enhance delivery
                                        efficiency.</p>
                                </div>
                            </div>
                            <img class="vector-img" src="assets/img/home4/vector/featurea-card-4-vector2.png" alt="">
                        </div>
                    </div>

                    <div class="col-xl-8 col-lg-12 wow animate fadeInUp" data-wow-delay="200ms"
                        data-wow-duration="1500ms">
                        <div class="feature-card one">
                            <div class="feature-content-wrap">
                                <div class="feature-icon">
                                    <img src="assets/img/home4/icon/feature-icon-img1.svg" alt="">
                                </div>
                                <div class="feature-content">
                                    <h3>Y — Your Trusted Partner</h3>
                                    <p>We go beyond logistics — building long-term trust and delivering peace of mind
                                        every
                                        time.</p>
                                </div>
                            </div>
                            <div class="feature-img-wrap">
                                <img src="assets/img/easyway/allaround.jpg" alt="">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 d-flex align-items-center justify-content-center">
                        <div class="tracking-btn mt-60">
                            <p>Connecting the world, one delivery at a time!</p>
                            <a class="track-btn" href="<?= e(url('quote.php')) ?>">
                                Request a Quote
                                <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M1 9L9 1M9 1C7.22222 1.33333 3.33333 2 1 1M9 1C8.66667 2.66667 8 6.33333 9 9"
                                        stroke-width="1.5" stroke-linecap="round"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Home4 Feature Card Section End -->
    </section>


    <!-- Home4 Image Area Section Start -->
    <div class="home2-company-banner-img  three">
    </div>
    <!-- Home4 Image Area Section End -->
    <section id="quote-section">
        <!-- Calculate Shipping Area Start -->
        <div id="quote" class="calculate-shipping-area mb-120 wow animate fadeInUp" data-wow-delay="200ms"
            data-wow-duration="1500ms">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-10">
                        <div class="calculate-shipping-area-wrapper">
                            <div class="section-title text-center">
                                <h2><span>Get </span>Shipping / Delivery Quote</h2>
                            </div>
                            <svg class="line" height="6" viewBox="0 0 956 6" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M5 2.5L0 0.113249V5.88675L5 3.5V2.5ZM951 3.5L956 5.88675V0.113249L951 2.5V3.5ZM4.5 3V3.5H951.5V3V2.5H4.5V3Z">
                                </path>
                            </svg>

                            <form id="quoteRequestForm" method="post" action="<?= e(url('controller/router.php?action=quote.submit')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_return" value="index.php">
                                <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                                <div class="check-area">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipment_type_option"
                                            id="domestic" value="Domestic" <?= (($homeQuoteState['data']['shipment_type'] ?? 'Domestic') === 'Domestic') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="domestic">
                                            Send Within Nigeria
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipment_type_option"
                                            id="international" value="International" <?= (($homeQuoteState['data']['shipment_type'] ?? '') === 'International') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="international">
                                            Send Outside Nigeria
                                        </label>
                                    </div>
                                </div>

                                <div class="row g-4 mb-50">
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>From <span>(Pickup Location)</span></label>
                                            <input type="text" name="from_location" value="<?= form_value($homeQuoteState, 'from_location') ?>" placeholder="e.g., Lagos, Nigeria"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>To <span>(Delivery Destination)</span></label>
                                            <input type="text" name="to_location" value="<?= form_value($homeQuoteState, 'to_location') ?>" placeholder="e.g., Abuja, Nigeria"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>Weight <span>(in Kilograms)</span></label>
                                            <select name="weight_range" required>
                                                <option value="">Select Weight Range</option>
                                                <?php foreach (['Below 1kg', '1kg - 5kg', '6kg - 15kg', '16kg - 30kg', 'Above 30kg'] as $option): ?>
                                                    <option value="<?= e($option) ?>" <?= (($homeQuoteState['data']['weight_range'] ?? '') === $option) ? 'selected' : '' ?>><?= e($option) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-inner">
                                            <label>Total Quantity</label>
                                            <input type="number" min="1" max="10000" name="quantity" value="<?= form_value($homeQuoteState, 'quantity', '1') ?>" placeholder="e.g., 2" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-inner">
                                            <label>Delivery Type</label>
                                            <select name="delivery_type" required>
                                                <option value="">Select Type</option>
                                                <?php foreach (['Standard Delivery', 'Express Delivery', 'Same-Day Delivery', 'Cargo / Freight'] as $option): ?>
                                                    <option value="<?= e($option) ?>" <?= (($homeQuoteState['data']['delivery_type'] ?? '') === $option) ? 'selected' : '' ?>><?= e($option) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Contact Information Section -->
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>Full Name</label>
                                            <input type="text" name="fullname" value="<?= form_value($homeQuoteState, 'full_name') ?>" placeholder="Your Full Name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>Email Address</label>
                                            <input type="email" name="email" value="<?= form_value($homeQuoteState, 'email') ?>" placeholder="Your Email Address" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-inner">
                                            <label>Phone Number</label>
                                            <input type="tel" name="phone" value="<?= form_value($homeQuoteState, 'phone') ?>" placeholder="Your Phone Number" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="btn-and-contact-area">
                                    <button class="primary-btn2 black-bg btn-hover" type="submit">
                                        Request Quote
                                        <svg width="10" height="10" viewBox="0 0 10 10"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g>
                                                <path
                                                    d="M5.83333 4.16667V0H4.16667V4.16667H0V5.83333H4.16667V10H5.83333V5.83333H10V4.16667H5.83333Z">
                                                </path>
                                            </g>
                                        </svg>
                                        <span></span>
                                    </button>
                                    <div class="contact">
                                        <div class="icon">
                                            <svg width="18" height="18" viewBox="0 0 18 18"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g>
                                                    <path
                                                        d="M17.5101 13.2102L14.9981 10.6982C14.101 9.8011 12.5759 10.16 12.217 11.3262C11.9479 12.1337 11.0508 12.5822 10.2434 12.4028C8.44911 11.9542 6.02686 9.62168 5.5783 7.73771C5.30916 6.93026 5.84744 6.03314 6.65485 5.76404C7.82112 5.40519 8.17997 3.88007 7.28284 2.98294L4.77089 0.470991C4.05319 -0.156997 2.97663 -0.156997 2.34864 0.470991L0.644104 2.17553C-1.06044 3.96978 0.82353 8.72455 5.04003 12.941C9.25652 17.1575 14.0113 19.1313 15.8055 17.337L17.5101 15.6324C18.1381 14.9147 18.1381 13.8382 17.5101 13.2102Z">
                                                    </path>
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="contact-content">
                                            <p>Need Help?</p>
                                            <a href="tel:<?= e(preg_replace('/\s+/', '', support_phone())) ?>"><?= e(support_phone()) ?></a>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p><strong>Note:</strong> Final pricing is confirmed after our team reviews the route, weight
                                and handling requirements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Calculate Shipping Area End -->


    <!-- Home4 Service Section Start -->
    <div id="services" class="home4-service-section mb-120">
        <div class="container">
            <div class="swiper home4-service-slider">
                <div class="swiper-wrapper">

                    <div class="swiper-slide">
                        <div class="service-card2">
                            <div class="service-card-img">
                                <img src="assets/img/home4/service-img1.png" alt="">
                            </div>
                            <div class="service-content">
                                <div class="icon">
                                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M49.2117 23.5993C46.6828 23.3876 45.2687 21.7337 44.2773 19.5759C43.6648 18.2275 43.1039 16.8642 42.4336 15.5376C42.2516 15.1876 41.7852 14.7798 41.4203 14.7579C39.6203 14.6556 37.8266 14.6486 36.0195 14.6486C35.3852 14.6415 35.1156 14.9548 35.1305 15.6759C35.1594 19.422 35.1523 23.1618 35.1523 26.9079H35.1305V29.7071C36.3079 28.5927 37.8678 27.972 39.4891 27.9728C43.0023 27.9728 45.8594 30.8376 45.8594 34.3501C45.8594 35.0353 45.75 35.6915 45.5461 36.3181H47.7547C48.2648 36.3767 48.6 36.3986 48.8039 36.3181H48.8109C48.9641 36.2595 49.0516 36.1431 49.1023 35.9392C49.1531 35.7423 49.175 35.4509 49.1828 35.0642C49.2625 31.2751 49.2117 27.4993 49.2117 23.5993ZM36.2594 22.1493V16.2962C37.9867 16.2962 39.707 16.2892 41.4195 16.3111C41.5219 16.3111 41.6602 16.4056 41.7547 16.515C41.7766 16.5298 41.7836 16.5439 41.7984 16.5736C41.8133 16.5806 41.8273 16.6025 41.8273 16.6243C42.0242 17.0759 42.2133 17.5282 42.4031 17.9798C42.9921 19.3672 43.5751 20.757 44.1523 22.1493H36.2594Z" />
                                        <path
                                            d="M39.3797 29.9048C36.8578 29.9048 34.7586 31.997 34.7516 34.504C34.7516 37.0548 36.8141 39.147 39.3289 39.154C41.8875 39.161 43.9937 37.0767 44.0008 34.5329C44.0078 32.0399 41.8727 29.9048 39.3797 29.9048ZM39.3359 36.647C38.1336 36.6321 37.2516 35.7282 37.2586 34.504C37.2586 33.3157 38.1992 32.4048 39.3945 32.4118C40.5898 32.4267 41.5008 33.3665 41.4937 34.5618C41.4859 35.7501 40.5531 36.654 39.3359 36.647ZM16.8508 30.3798C14.3289 30.3798 12.2297 32.472 12.2227 34.979C12.2227 37.5298 14.2852 39.622 16.8 39.629C19.3586 39.636 21.4648 37.5517 21.4719 35.0079C21.4797 32.5157 19.3437 30.3798 16.8508 30.3798ZM16.807 37.122C15.6047 37.1071 14.7227 36.2032 14.7297 34.979C14.7297 33.7907 15.6703 32.8798 16.8656 32.8868C18.0609 32.9017 18.9719 33.8415 18.9648 35.0368C18.9578 36.2251 18.0242 37.129 16.807 37.122Z" />
                                        <path
                                            d="M33.1855 11.8088C33.1855 18.5814 33.184 25.3549 33.1824 32.1275C33.1824 33.083 32.8801 33.3877 31.898 33.3908C30.9809 33.3947 30.0629 33.3736 29.1465 33.3892V33.4033H23.2309C22.8747 31.9819 22.0543 30.7201 20.8997 29.8178C19.7451 28.9155 18.3223 28.4244 16.857 28.4223C15.3917 28.4202 13.9675 28.9073 12.8104 29.8064C11.6533 30.7054 10.8293 31.965 10.4691 33.3853C10.2738 33.3853 10.0785 33.3838 9.8832 33.3822C9.26758 33.3767 8.93242 33.0213 8.93789 32.3752C8.94648 31.3767 8.96055 30.3791 8.92539 29.3822C8.91836 29.1541 8.78398 28.8807 8.61914 28.7166C8.41599 28.6609 8.23607 28.5415 8.10586 28.376C7.97361 28.2066 7.90183 27.9979 7.90195 27.783C7.90257 27.5269 8.00459 27.2814 8.18569 27.1003C8.36679 26.9192 8.61224 26.8172 8.86836 26.8166H12.6855C12.7543 26.8166 12.8223 26.8252 12.8863 26.8377H13.0832C13.8621 26.8322 14.3738 26.3986 14.359 25.7924C14.3465 25.2096 13.8668 24.8111 13.1129 24.7955C12.2574 24.7799 11.402 24.7955 10.5465 24.7924C9.21601 24.7885 7.88555 24.801 6.55586 24.765C5.91836 24.7478 5.50508 24.2846 5.56211 23.7057C5.6207 23.108 5.9582 22.7572 6.5832 22.7346C6.81992 22.7267 7.05664 22.7267 7.29492 22.7267C10.6457 22.7283 13.9965 22.7307 17.3457 22.7299C18.1754 22.7299 18.6629 22.3392 18.6777 21.6721C18.6918 20.9846 18.2332 20.5814 17.4004 20.5728C16.6402 20.5627 15.8793 20.5713 15.1191 20.5713H13.7332C13.7005 20.5741 13.6676 20.5754 13.6348 20.5752H9.81914C9.56288 20.5746 9.31727 20.4726 9.13592 20.2915C8.95457 20.1105 8.8522 19.865 8.85117 19.6088C8.85117 19.0767 9.28711 18.6424 9.81914 18.6424H10.1613C10.3082 18.6096 10.5004 18.615 10.652 18.6135C13.6465 18.608 16.6402 18.6103 19.6348 18.6103C20.252 18.6103 20.8723 18.6291 21.4871 18.5916C22.0621 18.5557 22.4051 18.2111 22.4566 17.6377C22.5051 17.1103 22.0746 16.6494 21.4996 16.5955C21.1934 16.5674 20.8824 16.5838 20.573 16.5838C16.716 16.5838 12.859 16.5838 9.00195 16.5807C8.99336 16.5822 8.98477 16.5822 8.97773 16.5822H5.51523C5.43506 16.5857 5.35478 16.5857 5.27461 16.5822H5.15977C4.90365 16.5816 4.6582 16.4796 4.4771 16.2985C4.29599 16.1174 4.19398 15.8719 4.19336 15.6158C4.19336 15.1236 4.5668 14.715 5.04492 14.6564C5.18008 14.5697 5.4457 14.6236 5.64648 14.6236C10.0199 14.6221 14.391 14.6236 18.7645 14.6236C20.8316 14.6252 22.8996 14.6322 24.9652 14.6236C25.9207 14.6205 26.4855 13.7963 26.1074 12.9955C25.8277 12.4041 25.3277 12.3267 24.7402 12.3283C17.2785 12.3385 9.8168 12.3369 2.35586 12.3353C2.21914 12.3353 2.09102 12.3252 1.97383 12.3057H1.73789C1.48177 12.305 1.23632 12.203 1.05522 12.0219C0.87412 11.8408 0.772103 11.5954 0.771484 11.3392C0.772103 11.0831 0.87412 10.8377 1.05522 10.6566C1.23632 10.4755 1.48177 10.3735 1.73789 10.3728H5.55508C5.57539 10.3728 5.59336 10.3744 5.61367 10.376C14.3371 10.3728 23.0605 10.3721 31.7855 10.3728C32.9332 10.3713 33.1855 10.6353 33.1855 11.8088Z" />
                                    </svg>
                                </div>
                                <h2>
                                    <a href="<?= e(url('services.php')) ?>">Pick up and drop off</a>
                                </h2>
                                <p>Convenient and reliable pickup and drop-off services tailored to your specific time
                                    schedule.</p>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="service-card2">
                            <div class="service-card-img">
                                <img src="assets/img/home4/service-img2.png" alt="">
                            </div>
                            <div class="service-content">
                                <div class="icon">
                                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <path
                                                d="M49.4773 48.2534H45.4907C47.9378 46.8469 49.5912 44.2041 49.5912 41.1802C49.5912 36.6862 45.9427 33.0301 41.4582 33.0301C41.1365 33.0301 40.8198 33.051 40.5078 33.0876L37.8489 18.5696C37.8269 18.4492 37.7633 18.3404 37.6693 18.2621C37.5753 18.1837 37.4568 18.1408 37.3345 18.1409H33.1566C32.3554 17.3231 30.8144 16.363 29.3179 15.4306C27.6183 14.3717 25.861 13.2768 24.8808 12.2235C23.9851 10.8457 22.6743 9.52655 20.9884 9.42196C18.8255 9.19589 16.7386 10.5545 16.0095 12.6702C15.5235 14.0725 15.12 15.502 14.8009 16.9513L13.6584 22.147C16.3846 21.8634 17.87 19.0563 17.8855 19.0263C17.9507 18.9057 18.0606 18.8155 18.1916 18.7751C18.3227 18.7347 18.4643 18.7474 18.5861 18.8103C18.7078 18.8733 18.8 18.9816 18.8428 19.1119C18.8855 19.2422 18.8754 19.384 18.8146 19.5069C18.7385 19.6539 16.9696 22.997 13.5532 23.2074C13.5776 24.1023 13.89 24.9654 14.444 25.6685C13.7489 27.4683 12.4732 30.5274 11.2381 33.4466C10.4122 33.1707 9.5472 33.03 8.67645 33.0301C4.19184 33.0301 0.54344 36.6862 0.54344 41.1802C0.54344 44.2042 2.19675 46.8469 4.64387 48.2534H0.523248C0.386378 48.2561 0.256047 48.3125 0.160229 48.4102C0.0644108 48.508 0.0107422 48.6394 0.0107422 48.7763C0.0107422 48.9132 0.0644108 49.0447 0.160229 49.1425C0.256047 49.2402 0.386378 49.2965 0.523248 49.2993H49.4772C49.6141 49.2966 49.7444 49.2403 49.8402 49.1425C49.9361 49.0447 49.9897 48.9133 49.9898 48.7764C49.9898 48.6395 49.9361 48.508 49.8403 48.4103C49.7445 48.3125 49.6142 48.2561 49.4773 48.2534ZM41.4581 34.0761C45.3659 34.0761 48.5452 37.263 48.5452 41.1802C48.5452 44.8788 45.7104 47.9248 42.1047 48.2534H40.8117C37.2059 47.9248 34.3712 44.8788 34.3712 41.1802C34.3712 37.883 36.624 35.1036 39.6676 34.3063L40.236 37.4096C38.6551 37.9277 37.5093 39.4217 37.5093 41.1802C37.5093 43.367 39.2807 45.1462 41.4582 45.1462C43.6356 45.1462 45.4071 43.367 45.4071 41.1802C45.4071 38.9933 43.6356 37.2142 41.4582 37.2142C41.3932 37.2142 41.3287 37.216 41.2644 37.2191L40.6963 34.1177C40.9493 34.0902 41.2036 34.0764 41.4581 34.0761ZM21.2384 15.4051C21.272 15.3452 21.3171 15.2925 21.3711 15.25C21.4251 15.2076 21.4869 15.1762 21.553 15.1576C21.6192 15.139 21.6883 15.1337 21.7565 15.1419C21.8247 15.15 21.8906 15.1715 21.9505 15.2052L31.2213 20.4117C31.5303 20.5861 31.8844 20.6644 32.2381 20.6366C32.5919 20.6088 32.9293 20.4761 33.2072 20.2555C33.5485 19.986 33.7185 19.5857 33.7013 19.1868H36.8984L37.8975 24.6425L21.9331 24.6548L21.5572 24.3095C21.4857 24.2437 21.4339 24.1594 21.4076 24.0659C21.3813 23.9724 21.3815 23.8734 21.4083 23.7801L23.3062 17.1663L21.4382 16.1173C21.3173 16.0493 21.2283 15.9361 21.1909 15.8025C21.1534 15.669 21.1705 15.526 21.2384 15.4051ZM27.6034 29.8643L23.0707 25.6999L37.5174 25.6889L27.364 38.7928L28.1278 31.2433C28.153 30.9886 28.1188 30.7314 28.0278 30.4921C27.9368 30.2528 27.7915 30.0379 27.6034 29.8643ZM15.2558 26.4514C15.2644 26.4576 15.2723 26.4645 15.2809 26.4706L23.3269 32.2571C23.3942 32.3055 23.4491 32.3693 23.4869 32.4431C23.5248 32.517 23.5445 32.5987 23.5445 32.6817V40.7098L16.7954 40.7152C16.6232 37.6638 14.7687 35.0567 12.1482 33.8118C13.3102 31.1088 14.524 28.2802 15.2558 26.4514ZM12.5977 40.7188C12.4434 39.3885 11.6315 38.2576 10.4977 37.663C10.9084 36.6988 11.3209 35.7354 11.7352 34.7728C13.9841 35.8559 15.577 38.0947 15.7466 40.7162L12.5977 40.7188ZM1.58938 41.1802C1.58938 37.263 4.76865 34.0761 8.67645 34.0761C9.42677 34.0761 10.1497 34.1946 10.8289 34.4121C10.4168 35.3827 10.0035 36.3528 9.58895 37.3223C9.29007 37.2507 8.9838 37.2144 8.67645 37.2143C6.499 37.2143 4.72752 38.9934 4.72752 41.1803C4.72752 43.3671 6.499 45.1463 8.67645 45.1463C10.6562 45.1463 12.2999 43.6755 12.5822 41.7649L15.7378 41.7623C15.4586 45.194 12.7376 47.9424 9.32304 48.2536H8.02987C4.4241 47.9248 1.58938 44.8788 1.58938 41.1802ZM12.7089 48.2534C14.9978 46.9378 16.5899 44.5404 16.7865 41.7613L23.5445 41.7558V43.8935C23.5445 44.802 24.2836 45.541 25.1921 45.541C25.6006 45.5421 25.9949 45.3909 26.298 45.1169C26.6011 44.843 26.7912 44.4659 26.8313 44.0593L27.1657 40.7544L38.2222 26.4137L39.4788 33.2753C35.9479 34.1635 33.3252 37.3701 33.3252 41.1801C33.3252 44.2041 34.9785 46.8467 37.4256 48.2533H12.7089V48.2534Z" />
                                            <path
                                                d="M29.3274 8.33447C31.0732 6.58863 31.0732 3.75805 29.3274 2.01221C27.5815 0.266366 24.751 0.266365 23.0051 2.01221C21.2593 3.75805 21.2593 6.58863 23.0051 8.33447C24.751 10.0803 27.5815 10.0803 29.3274 8.33447Z" />
                                            <path
                                                d="M4.79003 20.6389L12.6157 22.0188C13.6413 18.4703 14.0164 11.7291 17.0885 9.50172C17.031 9.18659 16.8785 8.89661 16.6514 8.67072C16.4243 8.44482 16.1335 8.29384 15.818 8.23805C15.818 8.23805 7.06012 6.70215 6.97005 6.70215C6.22326 6.70215 5.55969 7.23858 5.42561 7.99884L3.51728 18.8212C3.36706 19.6733 3.938 20.4886 4.79003 20.6389Z" />
                                        </g>
                                    </svg>
                                </div>
                                <h2>
                                    <a href="<?= e(url('services.php')) ?>">Home delivery services</a>
                                </h2>
                                <p>Fast, secure, and professional delivery of parcels and goods directly to your
                                    doorstep.</p>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="service-card2">
                            <div class="service-card-img">
                                <img src="assets/img/home4/service-img3.png" alt="">
                            </div>
                            <div class="service-content">
                                <div class="icon">
                                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M24.9997 42.1875H9.37467C9.06635 42.186 8.76534 42.0934 8.50941 41.9215C8.25348 41.7495 8.05401 41.5058 7.93603 41.221C7.81805 40.9361 7.7868 40.6228 7.84621 40.3202C7.90562 40.0177 8.05304 39.7394 8.26998 39.5203L22.7903 25L8.26998 10.4797C8.05153 10.2612 7.90277 9.98279 7.84251 9.67974C7.78224 9.37669 7.81319 9.06257 7.93142 8.7771C8.04966 8.49163 8.24987 8.24763 8.50676 8.07593C8.76365 7.90424 9.06568 7.81257 9.37467 7.8125H24.9997C25.4137 7.8125 25.8122 7.97656 26.1044 8.27031L41.7294 23.8953C41.8746 24.0403 41.9898 24.2125 42.0684 24.402C42.147 24.5916 42.1875 24.7948 42.1875 25C42.1875 25.2052 42.147 25.4084 42.0684 25.598C41.9898 25.7875 41.8746 25.9597 41.7294 26.1047L26.1044 41.7297C25.9595 41.875 25.7873 41.9902 25.5977 42.0688C25.4081 42.1474 25.2049 42.1877 24.9997 42.1875Z" />
                                    </svg>
                                </div>
                                <h2>
                                    <a href="<?= e(url('services.php')) ?>">Personal errands</a>
                                </h2>
                                <p>Let us handle your daily tasks, shopping, and other errands so you can save time.</p>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="service-card2">
                            <div class="service-card-img">
                                <img src="assets/img/home4/service-img4.png" alt="">
                            </div>
                            <div class="service-content">
                                <div class="icon">
                                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M49.5945 24.2628C49.5945 25.3696 48.6889 26.2751 47.5822 26.2751H39.5906C39.0241 26.2751 37.5432 25.7812 37.1455 25.3834L31.4947 19.7325C30.7121 18.9499 30.7121 17.6693 31.4947 16.8867C32.2773 16.1041 33.5578 16.1041 34.3404 16.8867L39.704 22.2504H47.5822C48.6889 22.2504 49.5945 23.156 49.5945 24.2627V24.2628ZM15.6367 23.7898C16.4284 24.5632 17.7089 24.5483 18.4823 23.7566L23.7831 18.3308L25.7154 18.3083L26.7465 14.2714L23.6228 14.3079C23.0563 14.3145 21.5813 14.8255 21.1882 15.2279L15.6036 20.9442C14.8302 21.7358 14.845 23.0163 15.6367 23.7898ZM39.341 35.5709L33.4223 30.7647L34.8694 25.0991L30.4988 20.7284C29.1665 19.3961 29.1665 17.2231 30.4988 15.8908C31.8312 14.5585 34.0041 14.5585 35.3364 15.8908L36.8378 17.3922L36.9976 16.7665C37.3731 15.296 36.4773 13.7853 35.0066 13.4098L31.4972 12.5134C30.0266 12.1377 28.516 13.0337 28.1404 14.5043L27.3298 17.6784L24.3509 29.3417L20.7197 36.4106L13.3353 35.9839C12.5535 35.9388 11.8824 36.5555 11.8449 37.3376L11.7551 39.2041C11.7175 39.9862 12.3268 40.657 13.1089 40.6946L23.0788 41.1738C23.483 41.1932 24.3372 41.0449 24.5185 40.6852L28.3926 33L29.0459 33.3888L35.2894 38.2509L35.6568 47.1287C35.6892 47.9111 36.3558 48.5248 37.1382 48.4924L39.0052 48.4151C39.7876 48.3828 40.4013 47.7161 40.3689 46.9337L39.9561 36.9609C39.9393 36.5565 39.6537 35.8245 39.341 35.5705V35.5709ZM49.2958 10.5461H47.0341V13.4581C47.0342 13.5505 47.016 13.642 46.9807 13.7274C46.9454 13.8128 46.8937 13.8904 46.8284 13.9558C46.7631 14.0211 46.6856 14.073 46.6002 14.1084C46.5149 14.1439 46.4234 14.1621 46.331 14.1622H43.373C43.2806 14.1621 43.1891 14.1439 43.1038 14.1085C43.0185 14.073 42.9409 14.0212 42.8756 13.9558C42.8103 13.8904 42.7586 13.8128 42.7233 13.7274C42.688 13.642 42.6698 13.5505 42.6699 13.4581V10.5461H40.4082C40.0204 10.5461 39.704 10.8624 39.704 11.2503V20.1378C39.704 20.5257 40.0204 20.8421 40.4082 20.8421H49.2958C49.6836 20.8421 50 20.5258 50 20.1378V11.2503C50 10.8625 49.6836 10.5461 49.2958 10.5461ZM45.628 10.5461H44.0763V12.754H45.628V10.5461ZM38.7748 6.22446H41.2398C41.3323 6.22446 41.4239 6.20625 41.5093 6.17086C41.5948 6.13547 41.6724 6.0836 41.7378 6.0182C41.8032 5.95281 41.8551 5.87518 41.8905 5.78974C41.9258 5.70429 41.9441 5.61272 41.9441 5.52024C41.9441 5.42776 41.9258 5.33618 41.8905 5.25074C41.8551 5.1653 41.8032 5.08767 41.7378 5.02228C41.6724 4.95688 41.5948 4.90501 41.5093 4.86962C41.4239 4.83423 41.3323 4.81601 41.2398 4.81601H38.4626C38.3569 4.53564 38.227 4.26506 38.0741 4.00736C37.4073 2.88322 36.3126 2.0136 34.9478 1.66501C33.5841 1.31672 32.2077 1.55455 31.0848 2.22054C29.9695 2.88211 29.1048 3.96501 28.7509 5.31491C28.7451 5.33402 28.74 5.35344 28.7359 5.37316C28.666 5.65253 28.6198 5.93731 28.5978 6.22446H38.775H38.7748ZM2.09256 27.8545C1.90578 27.8545 1.72666 27.9287 1.59459 28.0608C1.46253 28.1929 1.38833 28.372 1.38833 28.5588C1.38833 28.7455 1.46253 28.9247 1.59459 29.0567C1.72666 29.1888 1.90578 29.263 2.09256 29.263H14.6513C14.8381 29.263 15.0172 29.1888 15.1493 29.0567C15.2813 28.9247 15.3555 28.7455 15.3555 28.5588C15.3555 28.372 15.2813 28.1929 15.1493 28.0608C15.0172 27.9287 14.8381 27.8545 14.6513 27.8545H2.09256ZM8.78099 35.5433H1.81016C1.62339 35.5433 1.44427 35.6175 1.3122 35.7495C1.18013 35.8816 1.10594 36.0607 1.10594 36.2475C1.10594 36.4343 1.18013 36.6134 1.3122 36.7455C1.44427 36.8775 1.62339 36.9517 1.81016 36.9517H8.78099C8.96776 36.9517 9.14688 36.8775 9.27895 36.7455C9.41102 36.6134 9.48521 36.4343 9.48521 36.2475C9.48521 36.0607 9.41102 35.8816 9.27895 35.7495C9.14688 35.6175 8.96776 35.5433 8.78099 35.5433ZM15.7089 6.1971H23.807C23.9938 6.1971 24.1729 6.12291 24.305 5.99084C24.4371 5.85877 24.5113 5.67965 24.5113 5.49288C24.5113 5.3061 24.4371 5.12698 24.305 4.99491C24.1729 4.86284 23.9938 4.78865 23.807 4.78865H15.7089C15.5221 4.78865 15.343 4.86284 15.2109 4.99491C15.0788 5.12698 15.0046 5.3061 15.0046 5.49288C15.0046 5.67965 15.0788 5.85877 15.2109 5.99084C15.343 6.12291 15.5221 6.1971 15.7089 6.1971ZM12.5233 20.8701C12.5233 20.6834 12.4491 20.5042 12.3171 20.3722C12.185 20.2401 12.0059 20.1659 11.8191 20.1659H0.704225C0.517453 20.1659 0.338331 20.2401 0.206263 20.3722C0.074195 20.5042 0 20.6834 0 20.8701C0 21.0569 0.074195 21.236 0.206263 21.3681C0.338331 21.5002 0.517453 21.5744 0.704225 21.5744H11.819C12.0058 21.5744 12.1849 21.5002 12.317 21.3681C12.449 21.236 12.5233 21.0569 12.5233 20.8701ZM7.72515 13.8857H16.8871C17.0739 13.8857 17.253 13.8115 17.3851 13.6795C17.5172 13.5474 17.5913 13.3683 17.5913 13.1815C17.5913 12.9947 17.5172 12.8156 17.3851 12.6835C17.253 12.5515 17.0739 12.4773 16.8871 12.4773H7.72515C7.53838 12.4773 7.35926 12.5515 7.22719 12.6835C7.09512 12.8156 7.02093 12.9947 7.02093 13.1815C7.02093 13.3683 7.09512 13.5474 7.22719 13.6795C7.35926 13.8115 7.53838 13.8857 7.72515 13.8857ZM32.5947 11.7268C34.9482 12.3279 37.3434 10.9073 37.9445 8.55374C38.0215 8.25235 38.0665 7.94366 38.0787 7.63281H29.2872C29.3587 9.5296 30.662 11.2331 32.5947 11.7268Z" />
                                    </svg>
                                </div>
                                <h2>
                                    <a href="<?= e(url('services.php')) ?>">Intra state delivery</a>
                                </h2>
                                <p>Seamless and affordable delivery solutions within the state for businesses and
                                    individuals.</p>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="service-card2">
                            <div class="service-card-img">
                                <img src="assets/img/home4/service-img5.png" alt="">
                            </div>
                            <div class="service-content">
                                <div class="icon">
                                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <path
                                                d="M49.4773 48.2534H45.4907C47.9378 46.8469 49.5912 44.2041 49.5912 41.1802C49.5912 36.6862 45.9427 33.0301 41.4582 33.0301C41.1365 33.0301 40.8198 33.051 40.5078 33.0876L37.8489 18.5696C37.8269 18.4492 37.7633 18.3404 37.6693 18.2621C37.5753 18.1837 37.4568 18.1408 37.3345 18.1409H33.1566C32.3554 17.3231 30.8144 16.363 29.3179 15.4306C27.6183 14.3717 25.861 13.2768 24.8808 12.2235C23.9851 10.8457 22.6743 9.52655 20.9884 9.42196C18.8255 9.19589 16.7386 10.5545 16.0095 12.6702C15.5235 14.0725 15.12 15.502 14.8009 16.9513L13.6584 22.147C16.3846 21.8634 17.87 19.0563 17.8855 19.0263C17.9507 18.9057 18.0606 18.8155 18.1916 18.7751C18.3227 18.7347 18.4643 18.7474 18.5861 18.8103C18.7078 18.8733 18.8 18.9816 18.8428 19.1119C18.8855 19.2422 18.8754 19.384 18.8146 19.5069C18.7385 19.6539 16.9696 22.997 13.5532 23.2074C13.5776 24.1023 13.89 24.9654 14.444 25.6685C13.7489 27.4683 12.4732 30.5274 11.2381 33.4466C10.4122 33.1707 9.5472 33.03 8.67645 33.0301C4.19184 33.0301 0.54344 36.6862 0.54344 41.1802C0.54344 44.2042 2.19675 46.8469 4.64387 48.2534H0.523248C0.386378 48.2561 0.256047 48.3125 0.160229 48.4102C0.0644108 48.508 0.0107422 48.6394 0.0107422 48.7763C0.0107422 48.9132 0.0644108 49.0447 0.160229 49.1425C0.256047 49.2402 0.386378 49.2965 0.523248 49.2993H49.4772C49.6141 49.2966 49.7444 49.2403 49.8402 49.1425C49.9361 49.0447 49.9897 48.9133 49.9898 48.7764C49.9898 48.6395 49.9361 48.508 49.8403 48.4103C49.7445 48.3125 49.6142 48.2561 49.4773 48.2534ZM41.4581 34.0761C45.3659 34.0761 48.5452 37.263 48.5452 41.1802C48.5452 44.8788 45.7104 47.9248 42.1047 48.2534H40.8117C37.2059 47.9248 34.3712 44.8788 34.3712 41.1802C34.3712 37.883 36.624 35.1036 39.6676 34.3063L40.236 37.4096C38.6551 37.9277 37.5093 39.4217 37.5093 41.1802C37.5093 43.367 39.2807 45.1462 41.4582 45.1462C43.6356 45.1462 45.4071 43.367 45.4071 41.1802C45.4071 38.9933 43.6356 37.2142 41.4582 37.2142C41.3932 37.2142 41.3287 37.216 41.2644 37.2191L40.6963 34.1177C40.9493 34.0902 41.2036 34.0764 41.4581 34.0761ZM21.2384 15.4051C21.272 15.3452 21.3171 15.2925 21.3711 15.25C21.4251 15.2076 21.4869 15.1762 21.553 15.1576C21.6192 15.139 21.6883 15.1337 21.7565 15.1419C21.8247 15.15 21.8906 15.1715 21.9505 15.2052L31.2213 20.4117C31.5303 20.5861 31.8844 20.6644 32.2381 20.6366C32.5919 20.6088 32.9293 20.4761 33.2072 20.2555C33.5485 19.986 33.7185 19.5857 33.7013 19.1868H36.8984L37.8975 24.6425L21.9331 24.6548L21.5572 24.3095C21.4857 24.2437 21.4339 24.1594 21.4076 24.0659C21.3813 23.9724 21.3815 23.8734 21.4083 23.7801L23.3062 17.1663L21.4382 16.1173C21.3173 16.0493 21.2283 15.9361 21.1909 15.8025C21.1534 15.669 21.1705 15.526 21.2384 15.4051ZM27.6034 29.8643L23.0707 25.6999L37.5174 25.6889L27.364 38.7928L28.1278 31.2433C28.153 30.9886 28.1188 30.7314 28.0278 30.4921C27.9368 30.2528 27.7915 30.0379 27.6034 29.8643ZM15.2558 26.4514C15.2644 26.4576 15.2723 26.4645 15.2809 26.4706L23.3269 32.2571C23.3942 32.3055 23.4491 32.3693 23.4869 32.4431C23.5248 32.517 23.5445 32.5987 23.5445 32.6817V40.7098L16.7954 40.7152C16.6232 37.6638 14.7687 35.0567 12.1482 33.8118C13.3102 31.1088 14.524 28.2802 15.2558 26.4514ZM12.5977 40.7188C12.4434 39.3885 11.6315 38.2576 10.4977 37.663C10.9084 36.6988 11.3209 35.7354 11.7352 34.7728C13.9841 35.8559 15.577 38.0947 15.7466 40.7162L12.5977 40.7188ZM1.58938 41.1802C1.58938 37.263 4.76865 34.0761 8.67645 34.0761C9.42677 34.0761 10.1497 34.1946 10.8289 34.4121C10.4168 35.3827 10.0035 36.3528 9.58895 37.3223C9.29007 37.2507 8.9838 37.2144 8.67645 37.2143C6.499 37.2143 4.72752 38.9934 4.72752 41.1803C4.72752 43.3671 6.499 45.1463 8.67645 45.1463C10.6562 45.1463 12.2999 43.6755 12.5822 41.7649L15.7378 41.7623C15.4586 45.194 12.7376 47.9424 9.32304 48.2536H8.02987C4.4241 47.9248 1.58938 44.8788 1.58938 41.1802ZM12.7089 48.2534C14.9978 46.9378 16.5899 44.5404 16.7865 41.7613L23.5445 41.7558V43.8935C23.5445 44.802 24.2836 45.541 25.1921 45.541C25.6006 45.5421 25.9949 45.3909 26.298 45.1169C26.6011 44.843 26.7912 44.4659 26.8313 44.0593L27.1657 40.7544L38.2222 26.4137L39.4788 33.2753C35.9479 34.1635 33.3252 37.3701 33.3252 41.1801C33.3252 44.2041 34.9785 46.8467 37.4256 48.2533H12.7089V48.2534Z" />
                                            <path
                                                d="M29.3274 8.33447C31.0732 6.58863 31.0732 3.75805 29.3274 2.01221C27.5815 0.266366 24.751 0.266365 23.0051 2.01221C21.2593 3.75805 21.2593 6.58863 23.0051 8.33447C24.751 10.0803 27.5815 10.0803 29.3274 8.33447Z" />
                                            <path
                                                d="M4.78991 20.6389L12.6156 22.0188C13.6412 18.4703 14.0163 11.7291 17.0884 9.50172C17.0309 9.18659 16.8784 8.89661 16.6513 8.67072C16.4241 8.44482 16.1333 8.29384 15.8179 8.23805C15.8179 8.23805 7.05999 6.70215 6.96993 6.70215C6.22314 6.70215 5.55957 7.23858 5.42549 7.99884L3.51715 18.8212C3.36694 19.6733 3.93788 20.4886 4.78991 20.6389Z" />
                                        </g>
                                    </svg>
                                </div>
                                <h2>
                                    <a href="<?= e(url('services.php')) ?>">Inter state delivery</a>
                                </h2>
                                <p>Efficient long-distance shipping services across state lines, ensuring your cargo
                                    arrives on time.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="row mt-70">
                <div class="col-lg-12 d-flex align-items-center justify-content-center">
                    <div class="slider-btn-grp">
                        <div class="slider-btn service-slider-prev">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row justify-content-center">
        <div class="col-xxl-9 col-lg-10">
            <div class="section-title text-center mb-120">
                <h2>Our Services</h2>
            </div>
        </div>
    </div>

    <!-- Home4 Feature Section Start -->
    <div class="home4-feature-section mb-120">
        <div class="feature-slider-wrapper">

            <div class="swiper home4-feature-slider">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="row g-0">
                            <div class="col-xl-6">
                                <div class="feature-img">
                                    <img src="assets/img/easyway/pickupanddropoff.jpg" alt="">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="feature-wrapper">
                                    <div class="feature">
                                        <h2>Parcel Pick up and Drop off</h2>
                                        <ul class="feature-list">
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Fast and Secure
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Affordable Rates
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                2/3 days with delivery
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Custom Solutions
                                            </li>
                                        </ul>
                                        <p>With real-time tracking, on-time delivery guarantees, and a commitment to
                                            excellence, we make parcel delivery—big or small—simple and reliable.
                                        </p>
                                    </div>
                                    <img class="vector" src="assets/img/home4/vector/home4-feature-section-vector.png"
                                        alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="row g-0">
                            <div class="col-xl-6 ">
                                <div class="feature-img">
                                    <img src="assets/img/easyway/homedelivery.jpg" alt="">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="feature-wrapper">
                                    <div class="feature">
                                        <h2>Home Delivery Services.</h2>
                                        <ul class="feature-list">
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Real-Time Tracking
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Flexible Plans
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Transparent Pricing
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Dedicated Support
                                            </li>
                                        </ul>
                                        <p>With real-time tracking, on-time delivery guarantees, and a commitment to
                                            excellence, we make parcel delivery—big or small—simple and reliable.
                                        </p>
                                    </div>
                                    <img class="vector" src="assets/img/home4/vector/home4-feature-section-vector.png"
                                        alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="row g-0">
                            <div class="col-xl-6">
                                <div class="feature-img">
                                    <img src="assets/img/easyway/errand.jpg" alt="">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="feature-wrapper">
                                    <div class="feature">
                                        <h2>Personal Errands</h2>
                                        <ul class="feature-list">
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Global Coverage
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Customs Support
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Door-to-Door Delivery
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                On-Time Guarantee
                                            </li>
                                        </ul>
                                        <p>With real-time tracking, on-time delivery guarantees, and a commitment to
                                            excellence, we make parcel delivery—big or small—simple and reliable.
                                        </p>
                                    </div>
                                    <img class="vector" src="assets/img/home4/vector/home4-feature-section-vector.png"
                                        alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="row g-0">
                            <div class="col-xl-6">
                                <div class="feature-img">
                                    <img src="assets/img/easyway/intra state.jpg" alt="">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="feature-wrapper">
                                    <div class="feature">
                                        <h2>Intra State Delivery</h2>
                                        <ul class="feature-list">
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Global Coverage
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Customs Support
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Door-to-Door Delivery
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                On-Time Guarantee
                                            </li>
                                        </ul>
                                        <p>With real-time tracking, on-time delivery guarantees, and a commitment to
                                            excellence, we make parcel delivery—big or small—simple and reliable.
                                        </p>
                                    </div>
                                    <img class="vector" src="assets/img/home4/vector/home4-feature-section-vector.png"
                                        alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="row g-0">
                            <div class="col-xl-6">
                                <div class="feature-img">
                                    <img src="assets/img/easyway/receivepackagec.jpg" alt="">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="feature-wrapper">
                                    <div class="feature">
                                        <h2>Inter State Errands</h2>
                                        <ul class="feature-list">
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Global Coverage
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Customs Support
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                Door-to-Door Delivery
                                            </li>
                                            <li class="single-feature">
                                                <img src="assets/img/home4/icon/feature-list-icon.svg" alt="">
                                                On-Time Guarantee
                                            </li>
                                        </ul>
                                        <p>With real-time tracking, on-time delivery guarantees, and a commitment to
                                            excellence, we make parcel delivery—big or small—simple and reliable.
                                        </p>
                                    </div>
                                    <img class="vector" src="assets/img/home4/vector/home4-feature-section-vector.png"
                                        alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slider-btn-grp">
                <svg class="line" height="6" viewBox="0 0 487 6" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M5 2.5L0 0.113249V5.88675L5 3.5V2.5ZM482 3.5L487 5.88675V0.113249L482 2.5V3.5ZM4.5 3V3.5H482.5V3V2.5H4.5V3Z"
                        fill-opacity="0.15" />
                </svg>
                <div class="slider-btn feature-section-slider-prev">
                    <svg width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path
                                d="M8.59961 12C8.59961 9.17647 3.42756 6.94118 1.99961 6C3.68719 5.05882 8.59961 2.82353 8.59961 0"
                                stroke-width="2" />
                        </g>
                    </svg>

                </div>
                <div class="slider-btn feature-section-slider-next">
                    <svg width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path
                                d="M2.69995 12.7002C2.69995 9.87667 7.872 7.64137 9.29995 6.7002C7.61237 5.75902 2.69995 3.52372 2.69995 0.700195"
                                stroke-width="2" />
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <!-- Home4 Feature Section End -->

    <!-- Home4 Testimonial Section Start -->
    <div class="home4-testimonial-section mb-120">
        <div class="container">
            <div class="counter-and-rating-area mb-60 wow animate fadeInUp" data-wow-delay="200ms"
                data-wow-duration="2000ms">
                <div class="counter-area">
                    <ul class="counter-img-grp">
                        <li><img src="assets/img/home3/counter-people-img1.png" alt=""></li>
                        <li><img src="assets/img/home3/counter-people-img2.png" alt=""></li>
                        <li><img src="assets/img/home3/counter-people-img3.png" alt=""></li>
                        <li><img src="assets/img/home3/counter-people-img4.png" alt=""></li>
                    </ul>
                    <h6><strong>Customer-focused</strong> local, interstate and international delivery support.</h6>
                </div>
                <div class="trustpilot-rating-area">
                    <strong><i class="bi bi-chat-square-heart"></i></strong>
                    <div class="trustpilot-rating">
                        <div class="rating-area">
                            <span>What customers value about Easyway</span>
                        </div>
                    </div>
                </div>
            </div>

            <svg class="line" height="6" viewBox="0 0 1320 6" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M5 2.5L0 0.113249V5.88675L5 3.5V2.5ZM1315 3.5L1320 5.88675V0.113249L1315 2.5V3.5ZM4.5 3V3.5H1315.5V3V2.5H4.5V3Z" />
            </svg>

            <div class="testimonial-slider-area">
                <div class="swiper home4-testimonial-slider">
                    <div class="swiper-wrapper">

                        <!-- Testimonial 1 -->
                        <div class="swiper-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-content">
                                    <span>Fast & Reliable!</span>
                                    <p>Easyway always delivers my packages on time. Their tracking system keeps me
                                        updated
                                        from pickup to final delivery. Excellent service!</p>
                                </div>
                                <div class="client-info">
                                    <div class="client-img">
                                        <img src="assets/img/home4/testimonial-img1.png" alt="">
                                    </div>
                                    <div class="client-content">
                                        <h5>Chidi Okafor</h5>
                                        <p>Small Business Owner</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial 2 -->
                        <div class="swiper-slide">
                            <div class="testimonial-card two">
                                <div class="testimonial-content">
                                    <span>Excellent Customer Service!</span>
                                    <p>Their support team is available anytime I need help. Friendly, responsive and
                                        always
                                        willing to resolve delivery issues quickly.</p>
                                </div>
                                <div class="client-info">
                                    <div class="client-img">
                                        <img src="assets/img/home4/testimonial-img2.png" alt="">
                                    </div>
                                    <div class="client-content">
                                        <h5>Amara Bello</h5>
                                        <p>Fashion Retailer</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial 3 -->
                        <div class="swiper-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-content">
                                    <span>Safe & Secure Delivery!</span>
                                    <p>My fragile package was handled with care and arrived in perfect condition. Highly
                                        recommended for anyone who values safe delivery.</p>
                                </div>
                                <div class="client-info">
                                    <div class="client-img">
                                        <img src="assets/img/home4/testimonial-img3.png" alt="">
                                    </div>
                                    <div class="client-content">
                                        <h5>Tunde Adebayo</h5>
                                        <p>Online Vendor</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial 4 -->
                        <div class="swiper-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-content">
                                    <span>Great Nationwide Coverage!</span>
                                    <p>I regularly ship items across different states. Easyway has never disappointed —
                                        fast interstate delivery and very professional riders.</p>
                                </div>
                                <div class="client-info">
                                    <div class="client-img">
                                        <img src="assets/img/home4/testimonial-img1.png" alt="">
                                    </div>
                                    <div class="client-content">
                                        <h5>Fatima Yusuf</h5>
                                        <p>Entrepreneur</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="slider-btn-grp">
                    <div class="slider-btn testimonial-slider-prev">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <path
                                    d="M9.59979 12C9.59979 9.17647 4.42774 6.94118 2.99979 6C4.68737 5.05882 9.59979 2.82353 9.59979 0"
                                    stroke-width="2" />
                            </g>
                        </svg>
                    </div>
                    <div class="slider-btn testimonial-slider-next">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <path d="M3 12C3 9.17647 8.17205 6.94118 9.6 6C7.91242 5.05882 3 2.82353 3 0"
                                    stroke-width="2" />
                            </g>
                        </svg>
                    </div>
                </div>
            </div>

            <a href="<?= e(url('contact.php')) ?>" class="reviews-btn">
                Talk to our team
                <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 9L9 1M9 1C7.22222 1.33333 3.33333 2 1 1M9 1C8.66667 2.66667 8 6.33333 9 9"
                        stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Home4 Testimonial Section End -->

    <!-- Home4 Img Section Start -->
    <div class="home2-company-banner-img five mb-120">
        <div class="container">
            <span>Clear support from booking to delivery.</span>
            <div class="content wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
                <h2>Fast. Reliable.Trackable Deliveries</h2>
                <a class="primary-btn2 btn-hover" href="<?= e(url('quote.php')) ?>">
                    Start Your Journey
                    <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path
                                d="M5.83333 4.16667V0H4.16667V4.16667H0V5.83333H4.16667V10H5.83333V5.83333H10V4.16667H5.83333Z">
                            </path>
                        </g>
                    </svg>
                    <span></span>
                </a>
            </div>
        </div>
    </div>
    <!-- Home4 Img Section End -->

    <!-- home4 Faq Section Start -->
    <div id="faq" class="home4-faq-section mb-120">
        <div class="container">
            <div class="row justify-content-center mb-65 wow animate fadeInDown" data-wow-delay="200ms"
                data-wow-duration="1500ms">
                <div class="col-xxl-6 col-lg-5 col-md-7">
                    <div class="section-title text-center">
                        <h2>Frequently Asked Questions</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center g-lg-4 gy-5 mb-50">
                <div class="col-xl-8 col-lg-10">
                    <div class="faq-wrap">
                        <div class="accordion accordion-flush" id="accordionFlushExample">

                            <!-- FAQ 1 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="200ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseOne" aria-expanded="false"
                                        aria-controls="flush-collapseOne">What items can you pick up and
                                        deliver?</button>
                                </h5>
                                <div id="flush-collapseOne" class="accordion-collapse collapse show"
                                    aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        We handle a wide range of items including documents, parcels, groceries,
                                        personal
                                        items, small packages, and errands-related items. For restricted items, please
                                        contact our support team.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 2 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="400ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseTwo" aria-expanded="false"
                                        aria-controls="flush-collapseTwo">How fast is your delivery service?</button>
                                </h5>
                                <div id="flush-collapseTwo" class="accordion-collapse collapse"
                                    aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Delivery timelines depend on the service selected:
                                        <ul>
                                            <li><span>Pick Up & Drop Off:</span> 30 minutes – 2 hours</li>
                                            <li><span>Home Delivery:</span> Same day within city</li>
                                            <li><span>Intra-State Delivery:</span> Same day or next day</li>
                                            <li><span>Inter-State Delivery:</span> 1–3 working days</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 3 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="600ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseThree" aria-expanded="false"
                                        aria-controls="flush-collapseThree">Do you run personal errand
                                        services?</button>
                                </h5>
                                <div id="flush-collapseThree" class="accordion-collapse collapse"
                                    aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Yes! We assist with grocery runs, store pickups, bank errands, market errands,
                                        pharmacy pickups, and other personal tasks. Simply request the errand you need.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 4 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="800ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseFour" aria-expanded="false"
                                        aria-controls="flush-collapseFour">Do you deliver outside Nigeria?</button>
                                </h5>
                                <div id="flush-collapseFour" class="accordion-collapse collapse"
                                    aria-labelledby="flush-headingFour" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Yes, we offer international delivery services for documents and parcels.
                                        Delivery
                                        time depends on the destination country and chosen courier partner.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 5 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="800ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseFive" aria-expanded="false"
                                        aria-controls="flush-collapseFive">How do I track my delivery?</button>
                                </h5>
                                <div id="flush-collapseFive" class="accordion-collapse collapse"
                                    aria-labelledby="flush-headingFive" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Once your order is placed, you will receive a tracking link or rider contact.
                                        Our
                                        system allows real-time tracking from pickup to final delivery.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 6 -->
                            <div class="accordion-item wow animate fadeInDown" data-wow-delay="800ms"
                                data-wow-duration="1500ms">
                                <h5 class="accordion-header" id="flush-headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapseSix" aria-expanded="false"
                                        aria-controls="flush-collapseSix">What if my package gets damaged or
                                        lost?</button>
                                </h5>
                                <div id="flush-collapseSix" class="accordion-collapse collapse"
                                    aria-labelledby="flush-headingSix" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Your items are handled with care, but in rare cases of damage or loss, our
                                        support
                                        team will investigate immediately and provide compensation based on our delivery
                                        policy.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="button-area mt-35 d-flex justify-content-end wow animate fadeInUp"
                        data-wow-delay="200ms" data-wow-duration="1500ms">
                        <a class="enqiry-btn" href="<?= e(url('contact.php')) ?>">
                            Drop Your Question
                            <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 9L9 1M9 1C7.22222 1.33333 3.33333 2 1 1M9 1C8.66667 2.66667 8 6.33333 9 9"
                                    stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- home4 Faq Section End -->

    <!-- Home4 Contact Section Start -->
    <div id="contact" class="home2-contact-section two wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
        <div class=" container">
            <div class="row justify-content-end">
                <div class="col-lg-6">
                    <div class="contact-form-wrapper">
                        <form method="post" action="<?= e(url('controller/router.php?action=contact.submit')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_return" value="index.php">
                            <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-inner">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" value="<?= form_value($homeContactState, 'full_name') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-inner">
                                        <label>Company Name</label>
                                        <input type="text" name="company_name" value="<?= form_value($homeContactState, 'company_name') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-inner">
                                        <label>Your Email *</label>
                                        <input type="email" name="email" value="<?= form_value($homeContactState, 'email') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-inner">
                                        <label>Phone Number</label>
                                        <input type="tel" name="phone" value="<?= form_value($homeContactState, 'phone') ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-inner">
                                        <label>Message</label>
                                        <input type="hidden" name="subject" value="Callback request">
                                        <textarea name="message" required><?= form_value($homeContactState, 'message') ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-inner2 two">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="contactCheck22">
                                            <label class="form-check-label" for="contactCheck22">
                                                I consent to my data being processed according to the privacy policy
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="primary-btn2 btn-hover" type="submit">
                                Request Callback
                                <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path
                                            d="M5.83333 4.16667V0H4.16667V4.16667H0V5.83333H4.16667V10H5.83333V5.83333H10V4.16667H5.83333Z">
                                        </path>
                                    </g>
                                </svg>
                                <span></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Home2 Contact Section End -->

    <!-- Home4 Img Section Start -->
    <div class="home2-company-banner-img four">
    </div>
    <!-- Home4 Img Section End -->

    <!-- Footer Section Start -->
    <footer class="footer-section style-3">
        <div class="container">
            <div class="company-logo-and-contact-area">
                <div class="row gy-5">
                    <div class="col-lg-4">
                        <div class="footer-logo-and-social">
                            <div class="logo-area">
                                <a href="<?= e(url('index.php')) ?>"><img src="assets/img/easyway/logo.jpg" alt="Easyway Logistics"></a>
                            </div>
                            <p>Easyway Logistics, <?= e(company_address()) ?>. Fast &amp; reliable delivery nationwide and abroad.</p>
                            <ul class="social-list" aria-label="Easyway Logistics social media">
                                <li><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a></li>
                                <?php foreach (social_media_links() as $homepageSocial): ?>
                                <li><a href="<?= e($homepageSocial['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e('Easyway Logistics on ' . $homepageSocial['name'] . ' (opens in a new tab)') ?>" title="<?= e($homepageSocial['name']) ?>"><i class="bi <?= e($homepageSocial['icon']) ?>" aria-hidden="true"></i></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="contact-area">
                            <h2>Your Trusted Nigerian & International Courier Partner.</h2>
                            <ul class="mail-and-call">
                                <li>
                                    <div class="icon">
                                        <img src="assets/img/home1/icon/footer-mail.svg" alt="">
                                    </div>
                                    <div class="content">
                                        <p>Email Support</p>
                                        <a href="mailto:<?= e(support_email()) ?>"><?= e(support_email()) ?></a>
                                    </div>
                                </li>
                                <li>
                                    <div class="icon">
                                        <img src="assets/img/home1/icon/footer-call-icon.svg" alt="">
                                    </div>
                                    <div class="content">
                                        <p>Call Us</p>
                                        <?php foreach (support_phones() as $phone): ?><a class="d-block" href="tel:<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a><?php endforeach; ?>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-menu">
            <div class="container">
                <div class="row gy-5 justify-content-between">
                    <div class="col-xl-4 col-lg-3 col-md-4 col-sm-6">
                        <div class="footer-widget">
                            <div class="widget-title">
                                <h3>Quick Actions</h3>
                            </div>
                            <ul class="widget-list">
                                <li><a href="<?= e(url('tracking.php')) ?>">Track Shipment</a></li>
                                <li><a href="<?= e(url('quote.php')) ?>">Get a Quote</a></li>
                                <li><a href="<?= e(url('calculator.php')) ?>">Calculate a Rate</a></li>
                                <li><a href="<?= e(url(App\CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php')) ?>">Customer Account</a></li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="col-xl-2 col-lg-3 col-md-4 col-sm-6 d-flex justify-content-lg-start justify-content-md-center justify-content-sm-center">
                        <div class="footer-widget">
                            <div class="widget-title">
                                <h3>Company</h3>
                            </div>
                            <ul class="widget-list">
                                <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                                <li><a href="<?= e(url('services.php')) ?>">Delivery Services</a></li>
                                <li><a href="<?= e(url('destinations.php')) ?>">International Destinations</a></li>
                                <li><a href="<?= e(url('contact.php')) ?>">Contact Us</a></li>
                                <li><a href="<?= e(url('staff/login.php')) ?>">Staff Portal</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-lg-center justify-content-md-end">
                        <div class="footer-widget">
                            <div class="widget-title">
                                <h3>Services</h3>
                            </div>
                            <ul class="widget-list">
                                <li><a href="<?= e(url('services.php')) ?>">Pick-up & Drop-off</a></li>
                                <li><a href="<?= e(url('services.php')) ?>">Home Delivery Services</a></li>
                                <li><a href="<?= e(url('services.php')) ?>">Personal Errand Services</a></li>
                                <li><a href="<?= e(url('cargo-services.php')) ?>">Cargo Services</a></li>
                                <li><a href="<?= e(url('packaging-materials.php')) ?>">Packaging Materials</a></li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="col-xl-2 col-lg-3 col-md-4 col-sm-6 d-flex justify-content-lg-end justify-content-md-start justify-content-sm-center">
                        <div class="footer-widget">
                            <div class="widget-title">
                                <h3>Support</h3>
                            </div>
                            <ul class="widget-list">
                                <li><a href="<?= e(url('quote.php')) ?>">Request a Quote</a></li>
                                <li><a href="<?= e(url('tracking.php')) ?>">Track Shipment</a></li>
                                <li><a href="<?= e(url('contact.php')) ?>">Contact Support</a></li>
                                <li><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <p>© <?= date('Y') ?> Easyway Logistics. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Footer Section End -->

    <!--  Main jQuery  -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/jquery-ui.js"></script>
    <script src="assets/js/moment.min.js"></script>
    <script src="assets/js/daterangepicker.min.js"></script>

    <!-- Popper and Bootstrap JS -->
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <!-- Swiper slider JS -->
    <script src="assets/js/swiper-bundle.min.js"></script>
    <script src="assets/js/slick.js"></script>
    <!-- Waypoints JS -->
    <script src="assets/js/waypoints.min.js"></script>
    <!-- Counterup JS -->
    <script src="assets/js/jquery.counterup.min.js"></script>
    <!-- Wow JS -->
    <script src="assets/js/wow.min.js"></script>
    <!-- Nice Select JS -->
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <!-- Gsap  JS -->
    <script src="assets/js/gsap.min.js"></script>
    <script src="assets/js/ScrollTrigger.min.js"></script>
    <script src="assets/js/jquery.fancybox.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/select-dropdown.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/stage1.js"></script>
    <a class="whatsapp-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with Easyway Logistics on WhatsApp"><i class="bi bi-whatsapp"></i><span>WhatsApp</span></a>
</body>

</html>
