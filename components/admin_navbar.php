<?php
// Start session to check if student is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if student is logged in
$isStudentLoggedIn = isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
?>
<!-- HEADER -->
<header class="header">
  <div class="container header-container">

    <a href="../../index.php" class="logo-link">
      <div class="logo-icon">U</div>
      <span class="logo-text">
        UOC <span class="logo-sub">Voice</span>
      </span>
    </a>

    <!-- Desktop Nav -->
    <nav>
      <ul class="nav-menu">
        <li>
          <a href="javascript:void(0)"
             onclick="scrollToSection('hero')"
             class="nav-link active">
             Home
          </a>
        </li>

        <li>
          <a href="javascript:void(0)"
             onclick="scrollToSection('features')"
             class="nav-link">
             Quick Actions
          </a>
        </li>

        <li>
          <a href="javascript:void(0)"
             onclick="scrollToSection('activity')"
             class="nav-link">
             Recent Feed
          </a>
        </li>

        <li>
          <a href="javascript:void(0)"
             onclick="scrollToSection('faqs')"
             class="nav-link">
             FAQs
          </a>
        </li>
      </ul>
    </nav>

    <!-- Buttons -->
    <div class="header-actions">
      <?php if (!$isStudentLoggedIn): ?>
        <!-- Show Login and Register buttons when NOT logged in -->
        <button
          class="btn btn-secondary"
          onclick="window.location.href='pages/student_Management/studentLogin.html'">
          Sign In
        </button>

        <button
          class="btn btn-primary"
          onclick="window.location.href='pages/student_Management/register.html'">
          Register
        </button>
      <?php else: ?>
        <!-- Show Dashboard and Logout buttons when logged in -->
        <button
          class="btn btn-secondary"
          onclick="window.location.href='../../pages/student_Management/studentDashboard.php'">
          Dashboard
        </button>

        <button
          class="btn btn-primary"
          onclick="window.location.href='../../pages/student_Management/studentLogout.php'">
          Logout
        </button>
      <?php endif; ?>
    </div>

    <!-- Mobile Hamburger -->

    <button
      class="hamburger-btn"
      id="hamburgerBtn"
      aria-label="Toggle Navigation">

      <i class="ti ti-menu-2" id="hamburgerIcon"></i>

    </button>

  </div>
</header>

<!-- Mobile Menu -->

<div class="mobile-menu" id="mobileMenu">

  <nav class="nav-menu">

    <a href="javascript:void(0)"
       onclick="scrollToSection('hero')"
       class="nav-link">

       Home

    </a>

    <a href="javascript:void(0)"
       onclick="scrollToSection('features')"
       class="nav-link">

       Quick Actions

    </a>

    <a href="javascript:void(0)"
       onclick="scrollToSection('activity')"
       class="nav-link">

       Recent Feed

    </a>

    <a href="javascript:void(0)"
       onclick="scrollToSection('faqs')"
       class="nav-link">

       FAQs

    </a>

  </nav>

  <div class="header-actions">
    <?php if (!$isStudentLoggedIn): ?>
      <!-- Show Login and Register buttons when NOT logged in -->
      <button
        class="btn btn-secondary"
        onclick="window.location.href='pages/student_Management/studentLogin.html'">
        Sign In
      </button>

      <button
        class="btn btn-primary"
        onclick="window.location.href='pages/student_Management/register.html'">
        Register
      </button>
    <?php else: ?>
      <!-- Show Dashboard and Logout buttons when logged in -->
      <button
        class="btn btn-secondary"
        onclick="window.location.href='pages/student_Management/studentDashboard.php'">
        Dashboard
      </button>

      <button
        class="btn btn-primary"
        onclick="window.location.href='pages/student_Management/studentLogout.php'">
        Logout
      </button>
    <?php endif; ?>
  </div>

</div>