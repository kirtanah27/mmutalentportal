<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 1000px; padding: 40px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 40px;">About Our Team</h2>

    <div class="team-members" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; justify-items: center;">

        <!-- Team Member 1: You -->
        <div class="member-card" style="background-color: #1c1c1c; padding: 25px; border-radius: 12px; box-shadow: 0 0 15px rgba(255, 255, 255, 0.05); text-align: center;">
            <img src="assets/about_us_images/kirtanah.jpg" alt="Your Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #f4c95d; margin-bottom: 15px;">
            <h3 style="color: #f1f1f1; margin-bottom: 5px;">KIRTANAH A/P MANALAN</h3>
            <p style="color: #aaa; font-size: 0.9em; margin-bottom: 10px;">Student ID: 1211102813</p>
            <p style="color: #ccc; margin-bottom: 15px;">Lecture Section: TC1L</p>
            <a href="mailto:1211102813@student.mmu.edu.my" style="color: #f4c95d; text-decoration: none;">Contact Me</a>
        </div>

        <!-- Team Member 2 -->
        <div class="member-card" style="background-color: #1c1c1c; padding: 25px; border-radius: 12px; box-shadow: 0 0 15px rgba(255, 255, 255, 0.05); text-align: center;">
            <img src="assets/about_us_images/suren.jpeg" alt="Team Member 1" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #f4c95d; margin-bottom: 15px;">
            <h3 style="color: #f1f1f1; margin-bottom: 5px;">SURENTHIRAN A/L SHAMOSAMUGAM NATHAN</h3>
            <p style="color: #aaa; font-size: 0.9em; margin-bottom: 10px;">Student ID: 1211104053</p>
            <p style="color: #ccc; margin-bottom: 15px;">Lecture Section: TC1L</p>
            <a href="mailto:1211104053@student.mmu.edu.my" style="color: #f4c95d; text-decoration: none;">Contact Me</a>
        </div>

        <!-- Team Member 3 -->
        <div class="member-card" style="background-color: #1c1c1c; padding: 25px; border-radius: 12px; box-shadow: 0 0 15px rgba(255, 255, 255, 0.05); text-align: center;">
            <img src="assets/about_us_images/sharvin.jpeg" alt="Team Member 2" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #f4c95d; margin-bottom: 15px;">
            <h3 style="color: #f1f1f1; margin-bottom: 5px;">SHARVINTHIRAN </h3>
            <p style="color: #aaa; font-size: 0.9em; margin-bottom: 10px;">Student ID: 1211103808</p>
            <p style="color: #ccc; margin-bottom: 15px;">Lecture Section: TC1L</p>
            <a href="mailto:1211103808@student.mmu.edu.my" style="color: #f4c95d; text-decoration: none;">Contact Me</a>
        </div>

        <!-- Team Member 4 -->
        <div class="member-card" style="background-color: #1c1c1c; padding: 25px; border-radius: 12px; box-shadow: 0 0 15px rgba(255, 255, 255, 0.05); text-align: center;">
            <img src="assets/about_us_images/zharif.jpg" alt="Team Member 3" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #f4c95d; margin-bottom: 15px;">
            <h3 style="color: #f1f1f1; margin-bottom: 5px;">MUHAMMAD ZHARIF BIN MOHD FAIZAL</h3>
            <p style="color: #aaa; font-size: 0.9em; margin-bottom: 10px;">Student ID: 1171100428</p>
            <p style="color: #ccc; margin-bottom: 15px;">Lecture Section: TC1L</p>
            <a href="mailto:1171100428@student.mmu.edu.my" style="color: #f4c95d; text-decoration: none;">Contact Me</a>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
