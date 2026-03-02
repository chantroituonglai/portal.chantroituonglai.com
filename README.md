# Portal Chantroituonglai

A highly specialized Business Management Portal built on the **Perfex CRM (v2.3.2)** ecosystem. This project features advanced automation, AI integrations, and localized business modules tailored for the Vietnamese market and large-scale retail operations.

## 🚀 Key Features

### 🤖 Artificial Intelligence Integration

- **Dual AI Providers**: Seamlessly switch between **Google Gemini AI** and **OpenAI** for administrative tasks.
- **Auto-Ticket Triage**: Automated classification, priority setting, and summary generation for support tickets using LLMs.
- **Smart Replies**: Suggest context-aware responses and detect automated/promotional emails to reduce staff workload.

### 🔌 Automation & Crawlers

- **MM Vietnam Integration**: Automated order crawling from `supplier.mmvietnam.com`.
- **AEON B2B Integration**: Integrated data extraction and synchronization from AEON's supplier portal (`aeonvn.b2b.com.my`).
- **External Data Mapping**: Advanced SKU/Barcode mapping layer for synchronizing external retail data with internal inventory.

### 💬 Social & Notifications (Zalo Ecosystem)

- **Zalo OA**: Full Official Account integration for customer engagement.
- **Zalo ZNS**: Automated Zalo Notification Service for order updates, OTPs, and system alerts.
- **ChatPion Bridge**: Linking CRM tasks directly to marketing campaigns.
- **Twilio Voice & SMS**: Fallback notification layer for global communications.

### 🏢 Core Business Modules

- **HRM & Accounting**: Localized modules supporting Vietnamese accounting standards and human resource tracking.
- **Warehouse Management**: Advanced inventory tracking, delivery notes, and multi-warehouse support.
- **Affiliate & OKR**: Built-in modules for managing affiliate networks and tracking company-wide Objectives and Key Results.
- **WooCommerce Sync**: Bi-directional synchronization with WordPress/WooCommerce storefronts.

## 🛠 Tech Stack

- **Framework**: PHP 8.1+ / CodeIgniter 3
- **Database**: MySQL
- **Assets Management**: Grunt (Autoprefixer, Uglify, PostCSS)
- **APIs**: Restful API v2 with CORS protection

## 📦 Installation

1. Ensure your environment meets the **PHP 8.1** requirement.
2. Clone the repository.
3. Configure `application/config/app-config.php`.
4. Run `grunt` to compile assets if modifying CSS/JS.

---

© 2026 Chantroituonglai. Optimized for high-performance CRM operations.
