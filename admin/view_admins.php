<?php
session_start();
include '../db.php';

$result = $conn->query("SELECT * FROM admins ORDER BY admin_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Roster — UOC CMS</title>
  
  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  
  <!-- Shared Style Sheets -->
  <link rel="stylesheet" href="../css/index.css">
  
  <style>
    body {
      padding: 60px 20px;
    }
    .roster-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 40px;
      max-width: 900px;
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
    .role-badge {
      display: inline-flex;
      padding: 4px 10px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(255, 184, 0, 0.15);
      color: var(--accent);
      border: 1px solid rgba(255, 184, 0, 0.3);
    }
    .action-buttons {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .btn-delete {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 500;
      background: rgba(220, 53, 69, 0.15);
      color: #dc3545;
      border: 1px solid rgba(220, 53, 69, 0.3);
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
    }
    .btn-delete:hover {
      background: rgba(220, 53, 69, 0.25);
      border-color: rgba(220, 53, 69, 0.5);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1" style="top: 10%; left: 15%;"></div>
  <div class="blur-blob blob-2" style="bottom: 10%; right: 15%;"></div>

  <div class="roster-card">
    <a href="adminDashboard.php" class="back-home">
      <i class="ti ti-arrow-left"></i> Back to Dashboard
    </a>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div style="background: rgba(40, 167, 69, 0.15); border: 1px solid rgba(40, 167, 69, 0.4); color: #28a745; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div style="background: rgba(220, 53, 69, 0.15); border: 1px solid rgba(220, 53, 69, 0.4); color: #dc3545; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <h2 style="margin-top: 0; text-align: left; margin-bottom: 10px; font-family: var(--font-display); font-size: 2.25rem;">Admin List</h2>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 30px;">Overview of administrative officers registered to process student grievances.</p>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email Address</th>
          <th>Username</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        if ($result && $result->num_rows > 0) {
          while($row = $result->fetch_assoc()) { 
        ?>
        <tr>
          <td>#<?php echo $row['admin_id']; ?></td>
          <td style="font-weight: 600; color: var(--text-bright);"><?php echo $row['full_name']; ?></td>
          <td><?php echo $row['email']; ?></td>
          <td><code><?php echo $row['username']; ?></code></td>
          <td><span class="role-badge"><?php echo htmlspecialchars($row['role'] ?? 'Coordinator'); ?></span></td>
          <td>
            <div class="action-buttons">
              <a href="delete_admin.php?id=<?php echo $row['admin_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this admin?');">
                <i class="ti ti-trash"></i> Delete
              </a>
            </div>
          </td>
        </tr>
        <?php 
          } 
        } else {
        ?>
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No administrators found.</td>
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