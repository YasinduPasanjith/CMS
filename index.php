<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Online Complaint Management System for the University of Colombo. Voice your campus concerns and track real-time resolution status.">
  <title>UOC CMS — University of Colombo Online Complaint Management System</title>
  
  <!-- Tabler Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

  <!-- Decorative blur blobs in the background -->
  <div class="blur-blob blob-1"></div>
  <div class="blur-blob blob-2"></div>

  <!-- ── 1. HEADER / NAVBAR ── -->
  <header class="header">
    <div class="container header-container">
      <a href="#" class="logo-link">
        <div class="logo-icon">U</div>
        <span class="logo-text">UOC <span class="logo-sub">Voice</span></span>
      </a>

      <!-- Desktop Nav Menu -->
      <nav>
        <ul class="nav-menu">
          <li><a href="javascript:void(0)" onclick="scrollToSection('hero')" class="nav-link active">Home</a></li>
          <li><a href="javascript:void(0)" onclick="scrollToSection('features')" class="nav-link">Quick Actions</a></li>
          <li><a href="javascript:void(0)" onclick="scrollToSection('activity')" class="nav-link">Recent Feed</a></li>
          <li><a href="javascript:void(0)" onclick="scrollToSection('faqs')" class="nav-link">FAQs</a></li>
        </ul>
      </nav>

      <!-- Desktop Header Actions -->
      <div class="header-actions">
        <button class="btn btn-secondary" onclick="window.location.href='pages/student_Management/studentLogin.html'">Sign In</button>
        <button class="btn btn-primary" onclick="window.location.href='pages/student_Management/register.html'">Register</button>
      </div>

      <!-- Hamburger Menu Button (Mobile) -->
      <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Navigation">
        <i class="ti ti-menu-2" id="hamburgerIcon"></i>
      </button>
    </div>
  </header>

  <!-- Mobile Dropdown Navigation -->
  <div class="mobile-menu" id="mobileMenu">
    <nav class="nav-menu">
      <a href="javascript:void(0)" onclick="scrollToSection('hero')" class="nav-link">Home</a>
      <a href="javascript:void(0)" onclick="scrollToSection('features')" class="nav-link">Quick Actions</a>
      <a href="javascript:void(0)" onclick="scrollToSection('activity')" class="nav-link">Recent Feed</a>
      <a href="javascript:void(0)" onclick="scrollToSection('faqs')" class="nav-link">FAQs</a>
    </nav>
    <div class="header-actions">
      <button class="btn btn-secondary" onclick="window.location.href='pages/student_Management/studentLogin.html'">Sign In</button>
      <button class="btn btn-primary" onclick="window.location.href='pages/student_Management/register.html'">Register</button>
    </div>
  </div>

  <!-- ── 2. HERO SECTION ── -->
  <section id="hero" class="hero">
    <div class="container">
      <div class="hero-badge">
        <i class="ti ti-shield-check"></i> Official University of Colombo Portal
      </div>
      <h1 class="hero-title text-gradient">
        Voice Your Concerns. <span class="accent-gradient">Enhance Campus Life.</span>
      </h1>
      <p class="hero-description">
        Welcome to the UOC Online Complaint Management System. A transparent, efficient, and accountability-driven platform dedicated to resolving student academic, facility, and administrative concerns.
      </p>
      <div class="hero-cta">
        <button class="btn btn-accent" onclick="handleAction('submit')">
          <i class="ti ti-plus"></i> Submit a Complaint
        </button>
        <button class="btn btn-secondary" onclick="handleAction('track')">
          <i class="ti ti-eye"></i> Track Progress
        </button>
      </div>

      <!-- ── 3. STATS BAR ── -->
      <div class="stats-bar">
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-number" id="statComplaints">0</div>
            <div class="stat-label">Resolved Complaints</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="statRate">0</div>
            <div class="stat-label">Resolution Rate</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="statDays">0</div>
            <div class="stat-label">Avg. Response Time</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="statDepts">0</div>
            <div class="stat-label">Participating Units</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── 4. QUICK ACTIONS SECTION ── -->
  <section id="features" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Navigate UOC Voice</h2>
        <p class="section-subtitle">Select your entry point to manage issues, monitor statistics, or perform administrative tasks.</p>
      </div>

      <div class="actions-grid">
        <!-- Card 1 -->
        <div class="action-card" onclick="handleAction('submit')">
          <div class="action-icon">
            <i class="ti ti-message-circle-plus"></i>
          </div>
          <h3 class="action-title">Submit Complaint</h3>
          <p class="action-desc">Academic bottlenecks, facility breakdowns, library issues, or administrative delays — report them immediately.</p>
          <span class="action-link">Launch Form <i class="ti ti-arrow-right"></i></span>
        </div>

        <!-- Card 2 -->
        <div class="action-card" onclick="handleAction('track')">
          <div class="action-icon">
            <i class="ti ti-chart-dots"></i>
          </div>
          <h3 class="action-title">Track Status</h3>
          <p class="action-desc">Check the real-time processing stage of your submitted complaint and communicate with assigning officers.</p>
          <span class="action-link">Track Issues <i class="ti ti-arrow-right"></i></span>
        </div>

        <!-- Card 3 -->
        <div class="action-card" onclick="handleAction('reports')">
          <div class="action-icon">
            <i class="ti ti-report"></i>
          </div>
          <h3 class="action-title">View Reports</h3>
          <p class="action-desc">Read system transparency summaries, student rosters, and feedback trends published for UOC welfare auditing.</p>
          <span class="action-link">Open Reports <i class="ti ti-arrow-right"></i></span>
        </div>

        <!-- Card 4 -->
        <div class="action-card" onclick="handleAction('admin')">
          <div class="action-icon">
            <i class="ti ti-lock"></i>
          </div>
          <h3 class="action-title">Administration</h3>
          <p class="action-desc">Dedicated dashboard access for administrative officers to delegate, monitor, and update complaint tickets.</p>
          <span class="action-link">Admin Access <i class="ti ti-arrow-right"></i></span>
        </div>
      </div>
    </div>
  </section>

  <!-- ── 5. RECENT ACTIVITY FEED ── -->
  <section id="activity" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Live Resolutions Feed</h2>
        <p class="section-subtitle">Explore real-time status updates of active and recently resolved complaints within University of Colombo.</p>
      </div>

      <div class="recent-layout">
        <!-- Left Column: Complaints Feed -->
        <div class="complaint-list" id="complaintList">
          <!-- Dynamic items will be injected by js/index.js -->
          <div class="complaint-row">
            <div class="c-avatar" style="background:var(--blue-bg);color:var(--blue-dark)">--</div>
            <div class="c-info">
              <div class="c-title">Loading campus updates...</div>
              <div class="c-meta">Please wait while we connect to feed.</div>
            </div>
            <span class="badge badge-inprog">Pending</span>
          </div>
        </div>

        <!-- Right Column: System Showcase Panel -->
        <div class="showcase-panel">
          <div class="showcase-icon">
            <i class="ti ti-broadcast"></i>
          </div>
          <h3 class="showcase-title">Open & Transparent</h3>
          <p class="showcase-desc">
            We are committed to maintaining accountability across all University of Colombo campuses and faculties.
          </p>
          <ul class="showcase-bullets">
            <li><i class="ti ti-circle-check-filled"></i> End-to-End Complaint Tracking</li>
            <li><i class="ti ti-circle-check-filled"></i> Automatic Notification System</li>
            <li><i class="ti ti-circle-check-filled"></i> Strict Student Privacy Safeguards</li>
            <li><i class="ti ti-circle-check-filled"></i> Dedicated Faculty Coordinators</li>
          </ul>
          <button class="btn btn-primary" style="width: 100%;" onclick="scrollToSection('faqs')">
            How Resolution Works
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ── 6. FAQ SECTION ── -->
  <section id="faqs" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Got Questions?</h2>
        <p class="section-subtitle">Everything you need to know about the UOC Complaint Management system and workflow.</p>
      </div>

      <div class="faq-container">
        <!-- FAQ 1 -->
        <div class="faq-card">
          <details class="faq-details">
            <summary>Who can file a complaint on UOC Voice?</summary>
            <div class="faq-answer">
              Any registered student of the University of Colombo (including undergraduates, postgraduates, and diploma candidates) across all faculties and departments can submit complaints.
            </div>
          </details>
        </div>

        <!-- FAQ 2 -->
        <div class="faq-card">
          <details class="faq-details">
            <summary>What categories of complaints are supported?</summary>
            <div class="faq-answer">
              You can file complaints regarding academic delays, lecture hall facilities, library services, Wi-Fi outage/IT systems, hostel conditions, physical safety, and registration bottlenecks.
            </div>
          </details>
        </div>

        <!-- FAQ 3 -->
        <div class="faq-card">
          <details class="faq-details">
            <summary>Is my identity protected when I file a complaint?</summary>
            <div class="faq-answer">
              Yes, absolutely. Students have the option to file complaints as "Private," which ensures only the direct faculty coordinator and authorized welfare administrators can see their names and registration details.
            </div>
          </details>
        </div>

        <!-- FAQ 4 -->
        <div class="faq-card">
          <details class="faq-details">
            <summary>What is the average response and resolution time?</summary>
            <div class="faq-answer">
              Typically, complaints are reviewed and routed to target departments within 24 hours. The average campus-wide resolution time is 3.2 days, though complex academic matters may take slightly longer.
            </div>
          </details>
        </div>
      </div>
    </div>
  </section>

  <!-- ── 7. FOOTER ── -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="#" class="logo-link">
            <div class="logo-icon">U</div>
            <span class="logo-text">UOC <span class="logo-sub">Voice</span></span>
          </a>
          <p class="footer-desc">
            The official Online Complaint Management System of the University of Colombo. Empowering students, ensuring accountability, and building a better campus together.
          </p>
        </div>

        <div>
          <h4 class="footer-title">Navigation</h4>
          <ul class="footer-links">
            <li><a href="javascript:void(0)" onclick="scrollToSection('hero')" class="footer-link">Home</a></li>
            <li><a href="javascript:void(0)" onclick="scrollToSection('features')" class="footer-link">Quick Actions</a></li>
            <li><a href="javascript:void(0)" onclick="scrollToSection('activity')" class="footer-link">Activity Feed</a></li>
            <li><a href="javascript:void(0)" onclick="scrollToSection('faqs')" class="footer-link">FAQ Support</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Resources</h4>
          <ul class="footer-links">
            <li><a href="https://cmb.ac.lk" target="_blank" rel="noopener noreferrer" class="footer-link">UOC Main Portal</a></li>
            <li><a href="https://lib.cmb.ac.lk" target="_blank" rel="noopener noreferrer" class="footer-link">UOC Library</a></li>
            <li><a href="https://noc.cmb.ac.lk" target="_blank" rel="noopener noreferrer" class="footer-link">IT Services NOC</a></li>
            <li><a href="pages/register.html" class="footer-link">Student Registry</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Contact</h4>
          <ul class="footer-links">
            <li class="footer-link"><i class="ti ti-map-pin"></i> Cumaratunga Munidasa Mawatha, Colombo 00700, Sri Lanka</li>
            <li class="footer-link"><i class="ti ti-phone"></i> +94 11 258 1835</li>
            <li class="footer-link"><i class="ti ti-mail"></i> support@voice.cmb.ac.lk</li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="footer-copy">
          &copy; 2026 University of Colombo. All rights reserved. Designed for student welfare.
        </p>
        <div class="footer-socials">
          <a href="#" class="social-icon" aria-label="Facebook"><i class="ti ti-brand-facebook"></i></a>
          <a href="#" class="social-icon" aria-label="Twitter"><i class="ti ti-brand-twitter"></i></a>
          <a href="#" class="social-icon" aria-label="LinkedIn"><i class="ti ti-brand-linkedin"></i></a>
          <a href="https://cmb.ac.lk" class="social-icon" aria-label="Website"><i class="ti ti-world"></i></a>
        </div>
      </div>
    </div>
  </footer>

  <!-- ── 8. JAVASCRIPT LOGIC ── -->
  <script src="js/index.js"></script>

</body>
</html>