-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2026 at 11:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fom`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `created_date` int(10) NOT NULL,
  `isDeleted` int(1) NOT NULL,
  `name` text NOT NULL,
  `role` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_date`, `isDeleted`, `name`, `role`) VALUES
(11, 'alwesihusin@gmail.com', '25d55ad283aa400af464c76d713c07ad', 1759831557, 0, 'Hussein Alwesi', 'admin'),
(13, 'lana.shwaiter@gmail.com', '25d55ad283aa400af464c76d713c07ad', 1788341589, 0, 'Lana Shwaiter', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_token_hash` char(64) NOT NULL,
  `refresh_token_hash` char(64) NOT NULL,
  `access_expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `api_tokens`
--

INSERT INTO `api_tokens` (`id`, `user_id`, `access_token_hash`, `refresh_token_hash`, `access_expires_at`, `refresh_expires_at`, `revoked_at`, `created_at`) VALUES
(21, 11, 'a399caa14631af07b74fa2770c21cc1fd9c4ab7871550b282e688f10be1bc10d', '5fb9ec54b43bdba0d6e1670d6d4c0b638225a0a149a3f2b5ba772aea5b88bde2', '2026-09-15 12:48:27', '2026-10-15 12:03:27', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `id` int(11) NOT NULL,
  `slug` text NOT NULL,
  `title` text NOT NULL,
  `short_desc` text NOT NULL,
  `long_desc` text NOT NULL,
  `img` text NOT NULL,
  `inner_img` text NOT NULL,
  `price` int(11) NOT NULL,
  `international_number` text NOT NULL,
  `publisher` text NOT NULL,
  `author` text NOT NULL,
  `specialization` text NOT NULL,
  `publish_year` int(11) NOT NULL,
  `page_no` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `created_date` int(10) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `is_deleted` int(1) NOT NULL,
  `index_file` text NOT NULL,
  `file` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`id`, `slug`, `title`, `short_desc`, `long_desc`, `img`, `inner_img`, `price`, `international_number`, `publisher`, `author`, `specialization`, `publish_year`, `page_no`, `category`, `created_date`, `owner_id`, `is_deleted`, `index_file`, `file`) VALUES
(1, 'خطوات-تطوير-الذات', 'خطوات تطوير الذات', 'خطوات بسيطة لتحقيق حياة أفضل', 'خطوات بسيطة لتحقيق حياة أفضل', '/uploads/1788987600/6aa2872b2e261_خطوات-تطوير-الذات-scaled.jpg', '/uploads/1788987600/6aa2872b2fbca_خطوات-تطوير-الذات-scaled.jpg', 160, '9789948729532', 'دار الافاق العلمية', 'فهد أحمد المقبالي', 'تنمية الذات', 2026, 175, 421, 1788950391, 11, 0, '/uploads/1788987600/6aa2872b30aeb_Profile.pdf', '/uploads/1788987600/6aa2872b302d1_Profile.pdf'),
(7, 'test-book', 'Title', 'shortdesc', 'longdesc', '/uploads/1789333200/6aa7f64d39c2f_خطوات-تطوير-الذات-scaled.jpg', '/uploads/1789333200/6aa7f64d3dbd3_خطوات-تطوير-الذات-scaled.jpg', 300, '9789948841526', 'دار الافاق العلمية', 'حسين الويسي', 'تطوير العلم', 2022, 230, 422, 1789392461, 11, 0, '/uploads/1789333200/6aa7f64d3e484_Profile.pdf', '/uploads/1789333200/6aa7f64d3e0b2_Profile.pdf');

-- --------------------------------------------------------

--
-- Stand-in structure for view `books_with_avg_rate`
-- (See below for the actual view)
--
CREATE TABLE `books_with_avg_rate` (
`id` int(11)
,`slug` text
,`title` text
,`short_desc` text
,`long_desc` text
,`img` text
,`inner_img` text
,`price` int(11)
,`international_number` text
,`publisher` text
,`author` text
,`specialization` text
,`publish_year` int(11)
,`page_no` int(11)
,`category` int(11)
,`created_date` int(10)
,`owner_id` int(11)
,`is_deleted` int(1)
,`index_file` text
,`file` text
,`avg_rate` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `book_prices`
--

CREATE TABLE `book_prices` (
  `id` int(11) NOT NULL,
  `country` text NOT NULL,
  `price` int(11) NOT NULL,
  `created_date` int(10) NOT NULL,
  `is_deleted` int(1) NOT NULL,
  `owner` int(11) NOT NULL,
  `bookid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_prices`
--

INSERT INTO `book_prices` (`id`, `country`, `price`, `created_date`, `is_deleted`, `owner`, `bookid`) VALUES
(1, 'sa', 350, 1788952016, 0, 13, 1);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `slug` text NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `img` text NOT NULL,
  `img_inner` text NOT NULL,
  `owner` int(11) NOT NULL,
  `created_date` int(10) NOT NULL,
  `isDeleted` int(1) NOT NULL,
  `isActive` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `slug`, `title`, `description`, `img`, `img_inner`, `owner`, `created_date`, `isDeleted`, `isActive`) VALUES
(421, 'title-one', 'title 1', 'desc 1', '/uploads/1788901200/6aa132757c8a1_1779723565721.jpeg', '/uploads/1788901200/6aa132757ccf1_WhatsApp-Image-2026-08-30-at-3.11.29-PM.jpeg', 11, 0, 0, 1),
(422, 'title-two', 'title t', 'desc d', '/uploads/1788296400/6a97fe1eae897_WhatsApp-Image-2026-08-30-at-3.11.29-PM.jpeg', '/uploads/1788296400/6a97fe1eaeb63_WhatsApp-Image-2026-08-30-at-3.12.03-PM.jpeg', 11, 1788345886, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `contactform`
--

CREATE TABLE `contactform` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `phone` text NOT NULL,
  `email` text NOT NULL,
  `message` text NOT NULL,
  `created_date` int(10) NOT NULL,
  `status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contactform`
--

INSERT INTO `contactform` (`id`, `name`, `phone`, `email`, `message`, `created_date`, `status`) VALUES
(1, 'cus 1', '+962791573132', 'alwesihusin@gmail.com', 'i suggest to have some ..', 1788881734, 0);

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `slug` text NOT NULL,
  `title` text NOT NULL,
  `body` text NOT NULL,
  `created_date` int(10) NOT NULL,
  `owner` int(11) NOT NULL,
  `isDeleted` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`id`, `slug`, `title`, `body`, `created_date`, `owner`, `isDeleted`) VALUES
(7, 'google', 'https://g.co/kgs/MvQxfJ', '', 0, 0, 0),
(8, 'whatsapp', 'https://api.whatsapp.com/send?phone=971565030747&text=', '', 0, 0, 0),
(9, 'x', 'https://twitter.com/DarAlAfaq1', '', 0, 0, 0),
(10, 'instagram', 'https://instagram.com/daralafaq?igshid=YmMyMTA2M2Y=', '', 0, 0, 0),
(11, 'linkedin', 'https://www.linkedin.com/company/daralafaqpublishing', '', 0, 0, 0),
(12, 'facebook', 'https://www.facebook.com/profile.php?id=100077508139324', '', 0, 0, 0),
(13, 'short_info', 'من نحن ؟', 'دار الآفاق العلمية ناشر أكاديمي بدولة الإمارات العربية المتحدة، ومقرها الشارقة، متخصص في نشر الكتب العلمية والاكاديمية بكل التخصصات العلمية (قانون – شريعة – اعلام – علوم انسانية بكل فروعها – ادارة – اقتصاد…….الخ) ونعمل بكل جهد للارتقاء بالمحتوى العلمي والمخرجات التعليمية لمواكبة التطور والمساهمة في النهوض بالعملية التعليمية.', 0, 0, 0),
(14, 'phone', '+97165399203', '', 0, 0, 0),
(15, 'mobile', '+97152703177', '', 0, 0, 0),
(16, 'email', 'gm-batool@daralafaq.com', '', 0, 0, 0),
(17, 'map', '25.367384,55.394534', '', 0, 0, 0),
(18, 'about', 'About us', 'دار الآفاق العلمية ناشر أكاديمي بدولة الإمارات العربية المتحدة، ومقرها الشارقة، متخصص في نشر الكتب العلمية والاكاديمية بكل التخصصات العلمية (قانون – شريعة – اعلام – علوم انسانية بكل فروعها – ادارة – اقتصاد…….الخ) ونعمل بكل جهد للارتقاء بالمحتوى العلمي والمخرجات التعليمية لمواكبة التطور والمساهمة في النهوض بالعملية التعليمية.  مؤلفين هم نخبة من أساتذة الجامعات العاملين بدولة الإمارات العربية المتحدة وخارجها، نضع في الاعتبار مسألة توصيف المساقات وأن تكون مطابقة وفق الاعتماد الأكاديمي للجامعات والمعايير الدولية، لذلك فأن وبكل فخر لنا ان تكون كل اصداراتنا هي مقررات طلابية في كثير من جامعات دولة الإمارات العربية وخارجها.  ومن هذا نجد من الضروري ان تكون اصداراتنا متاحة لكل الطلاب والباحثين والأكاديميين بالمكتبات والمؤسسات العامة لمساعدتهم على البحث والاطلاع والمعرفة لأنها كتب علمية متخصصة وكثير منها مقررات جامعية والمشاركة في جميع المعارض المحلية والدولية.  دار الآفاق العلمية لديها نخبة من الاساتذة بجميع التخصصات العلمية تتعاون معنا هيئات استشارية ومحكمين علميين ومراقبين على الجودة، كما ان لدينا فريق متميز يعمل معنا في التنسيق والاخراج والتصميم .', 0, 0, 0),
(19, 'copyright', 'Copyright © Dar Al Afaq Publishing . All right reserved', '', 0, 0, 0),
(20, 'privacy-policy', 'Privacy Policy', 'This Privacy Policy describes how Dar Al Afaq Alilmiyah Publishing & Distribution collects, uses, and discloses information, and your choices about the collection and use of that information.\r\n\r\n1. Information We Collect\r\n\r\nPersonal Information: When you visit our website, we may collect certain personal information such as your name, email address, postal address, phone number, or payment information if you make a purchase.\r\n\r\nUsage Data: We may also collect information automatically when you visit our website, such as your IP address, browser type, referring website, pages you viewed, and the dates/times when you accessed the website.\r\n\r\n\r\n\r\n2. How We Use Your Information\r\n\r\nWe may use the information we collect for various purposes, including:\r\n\r\n\r\nTo provide, maintain, and improve our website and services.\r\n\r\nTo communicate with you about your account or transactions.\r\n\r\nTo personalize your experience on our website.\r\n\r\nTo send you promotional and marketing communications.\r\n\r\nTo detect, prevent, and address technical issues.\r\n\r\n3. Information Sharing and Disclosure\r\n\r\nWe may share your information in certain situations, including:\r\n\r\n\r\nWith service providers who assist us in operating our website and services.\r\n\r\nWith affiliates, subsidiaries, or other third parties for business purposes.\r\n\r\nIn response to legal requests or to protect our rights, property, and safety, or the rights, property, and safety of others.\r\n\r\nWith your consent or at your direction.\r\n\r\n4. Cookies and Tracking Technologies\r\n\r\nWe use cookies and similar tracking technologies to track activity on our website and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.\r\n\r\n5. Your Choices\r\n\r\nYou have choices about the collection, use, and sharing of your information. You can opt-out of certain communications or request that we delete your information.\r\n\r\n6. Data Security\r\n\r\nWe take reasonable measures to protect your information from unauthorized access, alteration, disclosure, or destruction.\r\n\r\n7. Changes to This Privacy Policy\r\n\r\nWe may update our Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date.\r\n\r\n8. Contact Us\r\n\r\nIf you have any questions or concerns about our Privacy Policy, please contact us at gm-batool@daralafaq.com or 052-7031777.\r\n\r\n', 0, 0, 0),
(21, 'orders-payments-delivery', 'Orders , Payments and Delivery', 'This Privacy Policy describes how Dar Al Afaq Alilmiyah Publishing & Distribution collects, uses, and discloses information, and your choices about the collection and use of that information.\r\n\r\n1. Information We Collect\r\n\r\nPersonal Information: When you visit our website, we may collect certain personal information such as your name, email address, postal address, phone number, or payment information if you make a purchase.\r\n\r\nUsage Data: We may also collect information automatically when you visit our website, such as your IP address, browser type, referring website, pages you viewed, and the dates/times when you accessed the website.\r\n\r\n\r\n\r\n2. How We Use Your Information\r\n\r\nWe may use the information we collect for various purposes, including:\r\n\r\n\r\nTo provide, maintain, and improve our website and services.\r\n\r\nTo communicate with you about your account or transactions.\r\n\r\nTo personalize your experience on our website.\r\n\r\nTo send you promotional and marketing communications.\r\n\r\nTo detect, prevent, and address technical issues.\r\n\r\n3. Information Sharing and Disclosure\r\n\r\nWe may share your information in certain situations, including:\r\n\r\n\r\nWith service providers who assist us in operating our website and services.\r\n\r\nWith affiliates, subsidiaries, or other third parties for business purposes.\r\n\r\nIn response to legal requests or to protect our rights, property, and safety, or the rights, property, and safety of others.\r\n\r\nWith your consent or at your direction.\r\n\r\n4. Cookies and Tracking Technologies\r\n\r\nWe use cookies and similar tracking technologies to track activity on our website and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.\r\n\r\n5. Your Choices\r\n\r\nYou have choices about the collection, use, and sharing of your information. You can opt-out of certain communications or request that we delete your information.\r\n\r\n6. Data Security\r\n\r\nWe take reasonable measures to protect your information from unauthorized access, alteration, disclosure, or destruction.\r\n\r\n7. Changes to This Privacy Policy\r\n\r\nWe may update our Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date.\r\n\r\n8. Contact Us\r\n\r\nIf you have any questions or concerns about our Privacy Policy, please contact us at gm-batool@daralafaq.com or 052-7031777.', 0, 0, 0),
(22, 'refund-returns-policy', 'Refund and Returns Policy', 'Refund and Cancellation Policy\r\n\r\nThis Refund and Cancellation Policy governs the manner in which DAR AL AFAQ ALILMIYAH PUBLISHING & DISTRIBUTION handles refunds and cancellations for products purchased through our website.\r\n\r\n1. Refunds\r\n\r\nThere is no return , no refund , no cancellation policy once the items are purchased .,\r\n\r\n2. Cancellations\r\n\r\nThere is no return , no refund , no cancellation policy once the items are purchased .,\r\n\r\n3. Contact Us\r\n\r\nIf you have any questions or concerns about our refund and cancellation policy, please contact us at gm-batool@daralafaq.com or 052-7031777.\r\n\r\n5. Changes to This Policy\r\n\r\nWe reserve the right to update or change our refund and cancellation policy at any time. Any changes will be effective immediately upon posting the updated policy on our website.', 0, 0, 0),
(23, 'company_profile', '/uploads/1788901200/6aa12fe043322_Profile.pdf', '', 0, 0, 0),
(24, 'book-list-download', '/uploads/1788901200/6aa12fe0436ad_Booklist2023-download.xlsx', '', 0, 0, 0),
(25, 'book-list-view', '/uploads/1788901200/6aa12fe043e62_Booklist2023-view.pdf', '', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `created_date` int(10) NOT NULL,
  `is_deleted` int(1) NOT NULL,
  `description` text NOT NULL,
  `img` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `userid`, `created_date`, `is_deleted`, `description`, `img`) VALUES
(1, 11, 1788945267, 0, 'text here', '/uploads/1788901200/6aa132b1941b2_WhatsApp-Image-2026-08-30-at-3.11.29-PM.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `created_date` int(10) NOT NULL,
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `userid`, `created_date`, `note`) VALUES
(11, 11, 1788341430, 'signin');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `created_date` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `status` text NOT NULL,
  `payment_response` text NOT NULL,
  `cart` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `created_date`, `owner_id`, `status`, `payment_response`, `cart`) VALUES
(3, 1789464814, 11, 'final result i mean', 'response comming from payment gateway', '[{\"id\":1,\"slug\":\"خطوات-تطوير-الذات\",\"title\":\"خطوات تطوير الذات\",\"short_desc\":\"خطوات بسيطة لتحقيق حياة أفضل\",\"long_desc\":\"خطوات بسيطة لتحقيق حياة أفضل\",\"img\":\"http://localhost:8080/apistructure/uploads/1788987600/6aa2872b2e261_خطوات-تطوير-الذات-scaled.jpg\",\"inner_img\":\"http://localhost:8080/apistructure/uploads/1788987600/6aa2872b2fbca_خطوات-تطوير-الذات-scaled.jpg\",\"price\":160,\"international_number\":\"9789948729532\",\"publisher\":\"دار الافاق العلمية\",\"author\":\"فهد أحمد المقبالي\",\"specialization\":\"تنمية الذات\",\"publish_year\":2026,\"page_no\":175,\"category\":{\"id\":421,\"slug\":\"title-one\",\"title\":\"title 1\",\"description\":\"desc 1\",\"img\":\"http://localhost:8080/apistructure/uploads/1788901200/6aa132757c8a1_1779723565721.jpeg\",\"img_inner\":\"http://localhost:8080/apistructure/uploads/1788901200/6aa132757ccf1_WhatsApp-Image-2026-08-30-at-3.11.29-PM.jpeg\",\"owner\":{\"id\":11,\"username\":\"alwesihusin@gmail.com\",\"created_date\":\"07.10.2025 1:05 pm\",\"isDeleted\":false,\"name\":\"Hussein Alwesi\",\"role\":\"admin\"},\"created_date\":\"01.01.1970 2:00 am\",\"isDeleted\":false,\"isActive\":true},\"created_date\":\"09.09.2026 1:39 pm\",\"owner_id\":11,\"is_deleted\":false,\"index_file\":\"http://localhost:8080/apistructure/uploads/1788987600/6aa2872b30aeb_Profile.pdf\",\"file\":\"http://localhost:8080/apistructure/uploads/1788987600/6aa2872b302d1_Profile.pdf\",\"avg_rate\":\"4.0000\",\"owner\":{\"id\":11,\"username\":\"alwesihusin@gmail.com\",\"created_date\":\"07.10.2025 1:05 pm\",\"isDeleted\":false,\"name\":\"Hussein Alwesi\",\"role\":\"admin\"},\"prices\":[{\"id\":1,\"country\":\"sa\",\"price\":350,\"created_date\":\"09.09.2026 2:06 pm\",\"is_deleted\":false,\"owner\":{\"id\":13,\"username\":\"lana.shwaiter@gmail.com\",\"created_date\":\"02.09.2026 12:33 pm\",\"isDeleted\":false,\"name\":\"Lana Shwaiter\",\"role\":\"user\"}}],\"qty\":2},{\"id\":7,\"slug\":\"test-book\",\"title\":\"Title\",\"short_desc\":\"shortdesc\",\"long_desc\":\"longdesc\",\"img\":\"http://localhost:8080/apistructure/uploads/1789333200/6aa7f64d39c2f_خطوات-تطوير-الذات-scaled.jpg\",\"inner_img\":\"http://localhost:8080/apistructure/uploads/1789333200/6aa7f64d3dbd3_خطوات-تطوير-الذات-scaled.jpg\",\"price\":300,\"international_number\":\"9789948841526\",\"publisher\":\"دار الافاق العلمية\",\"author\":\"حسين الويسي\",\"specialization\":\"تطوير العلم\",\"publish_year\":2022,\"page_no\":230,\"category\":{\"id\":422,\"slug\":\"title-two\",\"title\":\"title t\",\"description\":\"desc d\",\"img\":\"http://localhost:8080/apistructure/uploads/1788296400/6a97fe1eae897_WhatsApp-Image-2026-08-30-at-3.11.29-PM.jpeg\",\"img_inner\":\"http://localhost:8080/apistructure/uploads/1788296400/6a97fe1eaeb63_WhatsApp-Image-2026-08-30-at-3.12.03-PM.jpeg\",\"owner\":{\"id\":11,\"username\":\"alwesihusin@gmail.com\",\"created_date\":\"07.10.2025 1:05 pm\",\"isDeleted\":false,\"name\":\"Hussein Alwesi\",\"role\":\"admin\"},\"created_date\":\"02.09.2026 1:44 pm\",\"isDeleted\":false,\"isActive\":false},\"created_date\":\"14.09.2026 4:27 pm\",\"owner_id\":11,\"is_deleted\":false,\"index_file\":\"http://localhost:8080/apistructure/uploads/1789333200/6aa7f64d3e484_Profile.pdf\",\"file\":\"http://localhost:8080/apistructure/uploads/1789333200/6aa7f64d3e0b2_Profile.pdf\",\"avg_rate\":\"0.0000\",\"owner\":{\"id\":11,\"username\":\"alwesihusin@gmail.com\",\"created_date\":\"07.10.2025 1:05 pm\",\"isDeleted\":false,\"name\":\"Hussein Alwesi\",\"role\":\"admin\"},\"prices\":[],\"qty\":1}]');

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `bookid` int(11) NOT NULL,
  `nickname` text NOT NULL,
  `email` text NOT NULL,
  `review` text NOT NULL,
  `created_date` int(10) NOT NULL,
  `userid` int(11) NOT NULL,
  `rate` int(1) NOT NULL,
  `is_deleted` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rates`
--

INSERT INTO `rates` (`id`, `bookid`, `nickname`, `email`, `review`, `created_date`, `userid`, `rate`, `is_deleted`) VALUES
(2, 1, '', '', 'text here', 1788943306, 11, 4, 0);

-- --------------------------------------------------------

--
-- Structure for view `books_with_avg_rate`
--
DROP TABLE IF EXISTS `books_with_avg_rate`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `books_with_avg_rate`  AS SELECT `b`.`id` AS `id`, `b`.`slug` AS `slug`, `b`.`title` AS `title`, `b`.`short_desc` AS `short_desc`, `b`.`long_desc` AS `long_desc`, `b`.`img` AS `img`, `b`.`inner_img` AS `inner_img`, `b`.`price` AS `price`, `b`.`international_number` AS `international_number`, `b`.`publisher` AS `publisher`, `b`.`author` AS `author`, `b`.`specialization` AS `specialization`, `b`.`publish_year` AS `publish_year`, `b`.`page_no` AS `page_no`, `b`.`category` AS `category`, `b`.`created_date` AS `created_date`, `b`.`owner_id` AS `owner_id`, `b`.`is_deleted` AS `is_deleted`, `b`.`index_file` AS `index_file`, `b`.`file` AS `file`, coalesce(avg(`r`.`rate`),0) AS `avg_rate` FROM (`book` `b` left join `rates` `r` on(`b`.`id` = `r`.`bookid` and `r`.`is_deleted` = 0)) GROUP BY `b`.`id`, `b`.`slug`, `b`.`title`, `b`.`short_desc`, `b`.`long_desc`, `b`.`img`, `b`.`inner_img`, `b`.`price`, `b`.`international_number`, `b`.`publisher`, `b`.`author`, `b`.`specialization`, `b`.`publish_year`, `b`.`page_no`, `b`.`category`, `b`.`created_date`, `b`.`owner_id`, `b`.`is_deleted`, `b`.`index_file`, `b`.`file` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `access_token_hash` (`access_token_hash`),
  ADD KEY `refresh_token_hash` (`refresh_token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `book_prices`
--
ALTER TABLE `book_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contactform`
--
ALTER TABLE `contactform`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `book_prices`
--
ALTER TABLE `book_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=423;

--
-- AUTO_INCREMENT for table `contactform`
--
ALTER TABLE `contactform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
