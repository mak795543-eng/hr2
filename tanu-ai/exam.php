<?php
session_start();
include("../db.php");

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total = 0;

    foreach ($_POST as $qid => $answer) {
        $qid = intval(str_replace("q", "", $qid));
        $stmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
        $stmt->bind_param("i", $qid);
        $stmt->execute();
        $stmt->bind_result($correct);
        if ($stmt->fetch()) {
            $total++;
            if ($answer === $correct) {
                $score++;
            }
        }
        $stmt->close();
    }

    echo "<div class='bg-green-100 p-6 text-center rounded-xl max-w-md mx-auto mt-10'>
            <h1 class='text-2xl font-bold mb-2'>🎉 Exam Finished!</h1>
            <p class='text-lg'>You scored <strong>$score / $total</strong></p>
            <a href='index.php' class='btn btn-primary mt-4'>Try Again</a>
          </div>";
    exit;
}

// ✅ Fetch latest 5 generated questions
$result = $conn->query("SELECT * FROM questions ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>TANu AI Exam</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-2xl p-6">
    <h1 class="text-2xl font-bold mb-4">📝 Generated Exam</h1>
    <form method="post">
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="mb-6">
          <p class="font-semibold mb-2"><?= htmlspecialchars($row['question']) ?></p>
          <div class="pl-4 space-y-2">
            <label class="block">
              <input type="radio" name="q<?= $row['id'] ?>" value="a" required>
              <?= htmlspecialchars($row['option_a']) ?>
            </label>
            <label class="block">
              <input type="radio" name="q<?= $row['id'] ?>" value="b">
              <?= htmlspecialchars($row['option_b']) ?>
            </label>
            <label class="block">
              <input type="radio" name="q<?= $row['id'] ?>" value="c">
              <?= htmlspecialchars($row['option_c']) ?>
            </label>
            <label class="block">
              <input type="radio" name="q<?= $row['id'] ?>" value="d">
              <?= htmlspecialchars($row['option_d']) ?>
            </label>
          </div>
        </div>
      <?php endwhile; ?>
      <button type="submit" class="btn btn-success w-full">Submit Exam</button>
    </form>
  </div>
</body>
</html>
