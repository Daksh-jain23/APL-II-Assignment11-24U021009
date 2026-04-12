-- CREATE DATABASE IF NOT EXISTS ica2s_conference;
-- USE ica2s_conference;

DROP TABLE IF EXISTS sections;
CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_name VARCHAR(50) NOT NULL,
    content TEXT NOT NULL
);

INSERT INTO sections (menu_name, content) VALUES
('Home', '<h1 class="hero-title">International Conference on Advances in Artificial Intelligence for Society (ICA2S 2026)</h1><p class="hero-theme">Theme: AI for Social</p><p class="hero-date">December 11-12, 2026</p><p class="hero-venue">Venue: Indian Institute of Information Technology Bhopal, India</p><div class="hero-organizers"><p>Organized by:</p><p><strong>Indian Institute of Information Technology Bhopal, India</strong></p><p>in collaboration with University of Vizja, Warsaw, Poland</p></div><div class="marquee-container"><p class="hero-mode">The conference will be organized in hybrid (online + offline) mode.</p></div>'),
('Committee', '<h2>Organizing Committee</h2><ul style="list-style: none; text-align: center;"><li><strong>Patron:</strong> Prof. (Dr.) Ashutosh Kumar Singh, Director, IIIT Bhopal</li><li><strong>General Chair:</strong> Prof. (Dr.) Manda Venkatramana, VC, Gulf Medical University</li><li><strong>Conference Chair:</strong> Dr. Gautam Shrivastava, IIIT Bhopal</li><li><strong>Technical Program Chair:</strong> Prof. (Dr.) KC Santosh, University of South Dakota, USA</li></ul>'),
('Important Dates', '<h2>Important Dates & Details</h2><p>Mark your calendars for the upcoming deadlines.</p><ul><li><strong>Paper Submission Deadline (Extended):</strong> September 30, 2025</li><li><strong>Notification of Acceptance:</strong> October 15, 2025</li><li><strong>Camera Ready Paper Submission:</strong> October 30, 2025</li><li><strong>Conference Dates:</strong> December 11-12, 2026</li></ul><div style="margin-top:20px; padding:15px; background:#e8f4f8; border-left:4px solid #3498db;"><p><strong>Review Process:</strong> All submissions will undergo a double-blind peer review process.</p></div>'),
('Speakers', '<h2>Keynote Speakers</h2><div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;"><div class="card speaker-card" style="flex: 1; min-width: 300px;"><h3>Prof. Sanghamitra Bandyopadhyay</h3><p><em>Director, ISI Kolkata</em></p><p>Renowned expert in computational biology and machine learning.</p></div><div class="card speaker-card" style="flex: 1; min-width: 300px;"><h3>L C Mangal</h3><p><em>Director General, DRDO</em></p><p>Expert in satellite communication and defense technology.</p></div><div class="card speaker-card" style="flex: 1; min-width: 300px;"><h3>Dr. Jane Doe</h3><p><em>Professor, Stanford University</em></p><p>Pioneer in deep learning and computer vision.</p></div></div>'),
('Workshop', '<h2>Pre-Conference Workshop</h2><div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;"><div class="card theme-card"><h4>Generative AI</h4><p>Hands-on session with industry experts.</p></div><div class="card theme-card"><h4>Large Language Models</h4><p>Building applications with LLMs.</p></div></div>'),
('Submission', '<h2>Submission</h2><div class="card" style="text-align: center;"><h4>Paper Submission Guidelines</h4><p>Authors are invited to submit original research papers.</p><ul style="text-align: left; display: inline-block;"><li>Files must be in PDF format.</li><li>Maximum 6 pages.</li><li>IEEE Format.</li></ul><div style="margin-top: 20px;"><a href="#" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Download Template</a> <a href="#" style="background: #27ae60; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px;">Submit Paper</a></div></div>'),
('Special Session', '<h2>Special Sessions</h2><div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;"><div class="card theme-card"><h4>AI in Healthcare</h4><p>Transforming patient outcomes.</p></div><div class="card theme-card"><h4>Sustainable AI</h4><p>AI for a greener future.</p></div><div class="card theme-card"><h4>AI Education</h4><p>Revolutionizing learning systems.</p></div></div>'),
('Registration', '<h2>Registration</h2><div class="card"><table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%; text-align: center;"><tr><th>Category</th><th>Early Bird</th><th>Standard</th></tr><tr><td>Student (IEEE Member)</td><td>$200</td><td>$250</td></tr><tr><td>Academician</td><td>$300</td><td>$350</td></tr><tr><td>Industry</td><td>$400</td><td>$450</td></tr></table></div>'),
('Sponsorship', '<h2>Sponsorship</h2><div style="display: flex; gap: 20px; flex-wrap: wrap;"><div class="card theme-card" style="flex: 1;"><h3>Platinum</h3><p>$5000</p></div><div class="card theme-card" style="flex: 1;"><h3>Gold</h3><p>$3000</p></div><div class="card theme-card" style="flex: 1;"><h3>Silver</h3><p>$1500</p></div></div>'),
('Contact', '<h2>Contact Us</h2><div class="card" style="text-align: center;"><p><strong>Conference Secretariat</strong></p><p>Indian Institute of Information Technology Bhopal, India</p><p><strong>Email:</strong> ica2s@hotmail.com</p></div>');

-- Create new users table for Assignment 10
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(15) NOT NULL UNIQUE,
    gender VARCHAR(10) NOT NULL,
    education VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL,
    state VARCHAR(50) NOT NULL,
    city VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
