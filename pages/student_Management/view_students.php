<?php
include '../../db.php';

$sql = "SELECT * FROM students ORDER BY student_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student List — UOC CMS</title>
  
  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  
  <!-- Shared Style Sheets -->
  <link rel="stylesheet" href="../../css/index.css">
  
  <style>
    body {
      padding: 60px 20px;
    }
    .roster-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 40px;
      max-width: 950px;
      margin: 0 auto;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
      font-size: 0.95rem;
    }
    th, td {
      padding: 16px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }
    th {
      font-family: var(--font-display);
      font-weight: 600;
      color: var(--text-bright);
      background: rgba(102, 0, 151, 0.15);
      border-bottom: 2px solid rgba(102, 0, 151, 0.4);
    }
    td {
      color: var(--text-main);
    }
    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }
    .back-home {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-bottom: 24px;
      font-weight: 500;
      transition: var(--transition);
    }
    .back-home:hover {
      color: var(--accent);
      transform: translateX(-4px);
    }
    .faculty-badge {
      display: inline-flex;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(102, 0, 151, 0.15);
      color: #c982ff;
      border: 1px solid rgba(102, 0, 151, 0.3);
    }
  </style>
</head>
<body>

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1" style="top: 10%; left: 15%;"></div>
  <div class="blur-blob blob-2" style="bottom: 10%; right: 15%;"></div>

  <div class="roster-card">
    <a href="../index.php" class="back-home">
      <i class="ti ti-arrow-left"></i> Back to Homepage
    </a>

    <h2 style="margin-top: 0; text-align: left; margin-bottom: 10px; font-family: var(--font-display); font-size: 2.25rem;">Student List</h2>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 30px;">Overview of registered students authorized to submit academic and administrative complaints.</p>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Registration No</th>
          <th>Faculty</th>
          <th>Registered Date</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        if ($result && $result->num_rows > 0) {
          while($row = $result->fetch_assoc()) { 
        ?>
        <tr>
          <td>#<?php echo $row['student_id']; ?></td>
          <td style="font-weight: 600; color: var(--text-bright);"><?php echo $row['full_name']; ?></td>
          <td><?php echo $row['email']; ?></td>
          <td><code><?php echo $row['reg_no']; ?></code></td>
          <td><span class="faculty-badge"><?php echo htmlspecialchars($row['faculty']); ?></span></td>
          <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $row['created_at']; ?></td>
        </tr>
        <?php 
          } 
        } else {
        ?>
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No registered students found.</td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

</body>
</html>
<?php
$conn->close();
?>