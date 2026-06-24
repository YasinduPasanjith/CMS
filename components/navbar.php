<!-- HEADER -->
<header class="header">
  <div class="container header-container">

    <a href="../index.php" class="logo-link">
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

  </div>

</div>