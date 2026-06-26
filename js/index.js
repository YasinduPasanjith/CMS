/* ============================================================
   UOC Online Complaint Management System — script.js
   ============================================================ */

/* ── 1. HAMBURGER MENU ── */
const hamburgerBtn  = document.getElementById('hamburgerBtn');
const hamburgerIcon = document.getElementById('hamburgerIcon');
const mobileMenu    = document.getElementById('mobileMenu');

hamburgerBtn.addEventListener('click', () => {
  const isOpen = mobileMenu.classList.toggle('active');
  hamburgerIcon.className = isOpen ? 'ti ti-x' : 'ti ti-menu-2';
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.mobile-menu .nav-link').forEach(link => {
  link.addEventListener('click', () => {
    mobileMenu.classList.remove('active');
    hamburgerIcon.className = 'ti ti-menu-2';
  });
});

/* ── 2. ANIMATED STAT COUNTERS ── */
const statTargets = {
  statComplaints: { end: 1240, suffix: '',      decimal: false },
  statRate:       { end: 91,   suffix: '%',     decimal: false },
  statDays:       { end: 3.2,  suffix: ' days', decimal: true  },
  statDepts:      { end: 18,   suffix: '',      decimal: false },
};

function animateCounter(id, end, suffix, decimal) {
  const el      = document.getElementById(id);
  const duration = 1400;   // ms
  const interval = 16;     // ~60 fps
  const steps    = Math.ceil(duration / interval);
  const increment = end / steps;
  let current    = 0;

  const timer = setInterval(() => {
    current = Math.min(current + increment, end);
    el.textContent = (decimal ? current.toFixed(1) : Math.round(current)) + suffix;
    if (current >= end) clearInterval(timer);
  }, interval);
}

// Trigger counters when the stats bar scrolls into view
const statsBar = document.querySelector('.stats-bar');
const statsObserver = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) {
    Object.entries(statTargets).forEach(([id, cfg]) =>
      animateCounter(id, cfg.end, cfg.suffix, cfg.decimal)
    );
    statsObserver.disconnect();
  }
}, { threshold: 0.3 });

statsObserver.observe(statsBar);

/* ── 3. RENDER RECENT COMPLAINTS ── */
// Replace this array with a fetch() call to your API, e.g.:
//   const complaints = await fetch('/api/complaints?limit=4').then(r => r.json());
const complaints = [
  {
    initials: 'KP',
    bg: 'var(--blue-bg)',
    fg: 'var(--blue-dark)',
    title: 'Library book reservation system not working',
    meta: 'Kasun Perera · Faculty of Arts · 2 hours ago',
    status: 'open',
  },
  {
    initials: 'NS',
    bg: 'var(--teal-bg)',
    fg: 'var(--teal-dark)',
    title: 'Lecture hall projector malfunction in block C',
    meta: 'Nimesha Silva · IT Department · 1 day ago',
    status: 'inprog',
  },
  {
    initials: 'RJ',
    bg: 'var(--amber-bg)',
    fg: 'var(--amber-dark)',
    title: 'Student ID card delay — 3 weeks pending',
    meta: "Ranasinghe J. · Registrar's Office · 3 days ago",
    status: 'inprog',
  },
  {
    initials: 'AM',
    bg: 'var(--green-bg)',
    fg: 'var(--green-dark)',
    title: 'WiFi outage in the computer science lab',
    meta: 'Amaya M. · Network Services · 5 days ago',
    status: 'resolved',
  },
];

const statusLabel = {
  open:     'Open',
  inprog:   'In progress',
  resolved: 'Resolved',
};

function renderComplaints(data) {
  const list = document.getElementById('complaintList');
  list.innerHTML = data.map(c => `
    <div class="complaint-row" onclick="viewComplaint('${c.initials}')">
      <div class="c-avatar" style="background:${c.bg};color:${c.fg}">${c.initials}</div>
      <div class="c-info">
        <div class="c-title">${c.title}</div>
        <div class="c-meta">${c.meta}</div>
      </div>
      <span class="badge badge-${c.status}">${statusLabel[c.status]}</span>
    </div>
  `).join('');
}

renderComplaints(complaints);

/* ── 4. NAVIGATION HELPERS ── */

/**
 * Smooth-scroll to a section by its element id.
 * @param {string} id - The id of the target element.
 */
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Handle quick-action card clicks.
 * Replace the alert() calls with your router's navigation method,
 * e.g. window.location.href = routes[action]  or  router.push(routes[action])
 * @param {string} action - One of 'submit' | 'track' | 'admin' | 'reports'
 */
function handleAction(action) {
  const routes = {
    submit:  'pages/student_Management/register.html',
    track:   'pages/student_Management/view_students.php',
    admin:   'admin/index.php',
    reports: 'admin/view_admins.php',
  };
  window.location.href = routes[action];
}

/**
 * Navigate to the complaint detail page.
 * @param {string} id - Complaint identifier (initials used as placeholder).
 */
function viewComplaint(id) {
  alert(`Detailed view for Complaint Ticket (${id}) is under development. In a production system, this would retrieve specific record details from the database.`);
}

/** Open the login / sign-in page. */
function openLogin() {
  window.location.href = 'pages/student_Management/studentLogin.html';
}