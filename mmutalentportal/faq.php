<?php

session_start();
include_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user']['user_id'];
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
  <h2>Frequently Asked Questions</h2>

  <!-- Show FAQs -->
  <div class="faq-section" style="margin-bottom: 40px;">
    <?php
    $faqs = $pdo->query("SELECT * FROM faq ORDER BY faq_id DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($faqs) > 0):
        foreach ($faqs as $row): ?>
            <div class="faq-item" style="margin-bottom: 25px; background-color: #2c2c2c; padding: 20px; border-radius: 10px;">
                <h4 style="color: #f4c95d;"><?= htmlspecialchars($row['question']) ?></h4>
                <p style="margin-top: 10px;"><?= htmlspecialchars($row['answer']) ?></p>
            </div>
    <?php
        endforeach;
    else:
        echo "<p>No FAQs available yet.</p>";
    endif;
    ?>
  </div>

  <!-- Submit new questions -->
  <div class="faq-form">
    <h3 style="color: #f4c95d; margin-bottom: 10px;">Didn't find your answer?</h3>
    <form action="faq.php" method="POST">
      <textarea name="question" rows="4" placeholder="Type your question here..." required
        style="width: 100%; padding: 12px; border-radius: 8px; border: none; background-color: #2c2c2c; color: #f1f1f1;"></textarea>
      <button type="submit" name="submit_question" class="btn gold" style="margin-top: 15px;">Submit Question</button>
    </form>
  </div>
</div>


<?php include 'includes/footer.php'; ?>


<!-- Handle form submission -->
<?php
if (isset($_POST['submit_question'])) {
    $question = trim($_POST['question']);
    $stmt = $pdo->prepare("INSERT INTO faqsubmission (user_id, question, status) VALUES (?, ?, 'pending')");
    if ($stmt->execute([$user_id, $question])) {
        echo "<script>alert('Your question has been submitted.'); window.location='faq.php';</script>";
    } else {
        echo "<p>Error submitting question.</p>";
    }
}
?>
