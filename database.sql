CREATE DATABASE IF NOT EXISTS ithmar_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ithmar_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS spending_tracking;
DROP TABLE IF EXISTS qr_payments;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS funding_transactions;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  password VARCHAR(255),
  role ENUM('project_owner','funder','supplier','admin'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT,
  title VARCHAR(255),
  description TEXT,
  funding_goal DECIMAL(10,2),
  current_funding DECIMAL(10,2) DEFAULT 0,
  purpose VARCHAR(100),
  category VARCHAR(100),
  city VARCHAR(100),
  impact_summary TEXT,
  status ENUM('pending','approved','rejected','funded') DEFAULT 'pending',
  risk_score ENUM('low','medium','high') DEFAULT 'low',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  balance DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE funding_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT,
  funder_id INT,
  amount DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_funding_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_funding_funder FOREIGN KEY (funder_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  business_name VARCHAR(255),
  category VARCHAR(100),
  qr_code VARCHAR(255),
  CONSTRAINT fk_suppliers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT,
  owner_id INT,
  supplier_id INT,
  amount DECIMAL(10,2),
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qr_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_qr_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_qr_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spending_tracking (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT,
  payment_id INT,
  description TEXT,
  amount DECIMAL(10,2),
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tracking_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_tracking_payment FOREIGN KEY (payment_id) REFERENCES qr_payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, full_name, email, phone, password, role) VALUES
(1, 'مدير منصة إثمار', 'admin@ithmar.ps', '0599000000', '123456', 'admin'),
(2, 'ليان الخطيب', 'owner@ithmar.ps', '0599111111', '123456', 'project_owner'),
(3, 'سما عوض', 'owner2@ithmar.ps', '0599222222', '123456', 'project_owner'),
(4, 'مؤسسة نمو للتنمية', 'funder@ithmar.ps', '0599333333', '123456', 'funder'),
(5, 'شركة سند للاستثمار المجتمعي', 'funder2@ithmar.ps', '0599444444', '123456', 'funder'),
(6, 'مورد الأمل للمواد', 'supplier@ithmar.ps', '0599555555', '123456', 'supplier'),
(7, 'شركة القدس للتجهيزات', 'supplier2@ithmar.ps', '0599666666', '123456', 'supplier'),
(8, 'إدارة إثمار الرئيسية', 'Admin@gmail.com', '0599777777', '123456', 'admin');

INSERT INTO wallets (user_id, balance) VALUES
(1, 0.00),
(2, 14400.00),
(3, 14500.00),
(4, 0.00),
(5, 0.00),
(6, 0.00),
(7, 0.00),
(8, 0.00);

INSERT INTO suppliers (id, user_id, business_name, category, qr_code) VALUES
(1, 6, 'مورد الأمل للمواد', 'مواد', 'QR-SUP-001'),
(2, 7, 'شركة القدس للتجهيزات', 'معدات وخدمات', 'QR-SUP-002');

INSERT INTO projects (id, owner_id, title, description, funding_goal, current_funding, purpose, category, city, impact_summary, status, risk_score) VALUES
(1, 2, 'مطبخ ليان الإنتاجي في رام الله', 'مطبخ منزلي صغير ينتج وجبات صحية ومربى موسمي للأسر والموظفين. التمويل مخصص لشراء مواد خام وتغليف يحافظ على جودة المنتج أثناء التوصيل.', 18000.00, 12400.00, 'مواد', 'غذاء', 'رام الله', 'دخل شهري أكثر ثباتاً لصاحبة المشروع وفرص عمل جزئية لسيدتين من الحي.', 'approved', 'low'),
(2, 2, 'ورشة تطريز منزلي من بيت لحم', 'ورشة تقودها سيدات لإنتاج قطع تطريز فلسطيني حديثة وبيعها عبر صفحات التواصل والمعارض المحلية. تحتاج الحملة إلى تصوير منتجات وحملات تسويق بسيطة.', 12000.00, 5000.00, 'تسويق', 'حرف يدوية', 'بيت لحم', 'توسيع المبيعات والحفاظ على حرفة محلية بأرباح عادلة للسيدات المشاركات.', 'approved', 'medium'),
(3, 3, 'متجر منتجات طبيعية من جنين', 'مشروع لإنتاج الصابون الطبيعي والأعشاب المجففة من موردين محليين، ويحتاج إلى معدات تعبئة وتجفيف صغيرة لتحسين الطاقة الإنتاجية.', 21000.00, 21000.00, 'معدات', 'منتجات طبيعية', 'جنين', 'زيادة الإنتاج المحلي وتوفير دخل موسمي مستقر لعائلتين.', 'funded', 'medium'),
(4, 3, 'ورشة خياطة صغيرة في غزة', 'ورشة خياطة منزلية تسعى لشراء ماكينة إضافية ومواد أولية لإنتاج زي مدرسي وحقائب قماشية بأسعار مناسبة.', 32000.00, 0.00, 'معدات', 'خياطة', 'غزة', 'تشغيل ثلاث سيدات وتوفير منتجات أساسية محلية بتكلفة أقل.', 'pending', 'high');

INSERT INTO funding_transactions (project_id, funder_id, amount, created_at) VALUES
(1, 4, 7400.00, '2026-04-18 10:15:00'),
(1, 5, 5000.00, '2026-04-21 12:30:00'),
(2, 4, 5000.00, '2026-04-23 09:20:00'),
(3, 5, 21000.00, '2026-04-25 14:45:00');

INSERT INTO qr_payments (id, project_id, owner_id, supplier_id, amount, category, created_at) VALUES
(1, 1, 2, 1, 1800.00, 'مواد', '2026-04-26 11:10:00'),
(2, 1, 2, 2, 1200.00, 'تسويق', '2026-04-27 13:05:00'),
(3, 3, 3, 2, 4500.00, 'معدات', '2026-04-28 10:00:00'),
(4, 3, 3, 1, 2000.00, 'خدمات', '2026-04-29 16:25:00');

INSERT INTO spending_tracking (project_id, payment_id, description, amount, category, created_at) VALUES
(1, 1, 'دفع عبر QR إلى مورد الأمل للمواد', 1800.00, 'مواد', '2026-04-26 11:10:00'),
(1, 2, 'دفع عبر QR إلى شركة القدس للتجهيزات', 1200.00, 'تسويق', '2026-04-27 13:05:00'),
(3, 3, 'دفع عبر QR إلى شركة القدس للتجهيزات', 4500.00, 'معدات', '2026-04-28 10:00:00'),
(3, 4, 'دفع عبر QR إلى مورد الأمل للمواد', 2000.00, 'خدمات', '2026-04-29 16:25:00');
