function loadSection(id, file, callback) {
  fetch(`./sections/${file}`)
    .then(res => {
      if (!res.ok) throw new Error(`Failed to load ${file}`);
      return res.text();
    })
    .then(data => {
      const el = document.getElementById(id);
      if (!el) return;

      el.innerHTML = data;

      requestAnimationFrame(() => {
        if (callback) callback();
      });
    })
    .catch(err => console.error(err));
}


function initNavbar() {
  const menuBtn = document.getElementById("menuBtn");
  const mobileMenu = document.getElementById("mobileMenu");
  const navbar = document.querySelector("nav");

  if (!menuBtn || !mobileMenu || !navbar) return;

  const newBtn = menuBtn.cloneNode(true);
  menuBtn.replaceWith(newBtn);

  newBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });

  const navLinks = navbar.querySelectorAll("a[href^='#']");
  const navLinkMap = Array.from(navLinks).reduce((map, link) => {
    const href = link.getAttribute("href");
    if (href) map[href] = true;
    return map;
  }, {});

  const setActiveLink = (href) => {
    navLinks.forEach(link => {
      link.classList.remove("text-sky", "font-semibold");
      if (href && link.getAttribute("href") === href) {
        link.classList.add("text-sky", "font-semibold");
      }
    });
  };

  const updateSectionActive = () => {
    const sectionIds = ["hero", "features", "tools", "about"];
    const scrollPoint = window.scrollY + window.innerHeight * 0.35;
    let activeSection = "hero";

    sectionIds.forEach(id => {
      const section = document.getElementById(id);
      if (!section) return;
      const top = section.offsetTop;
      if (top <= scrollPoint) {
        activeSection = id;
      }
    });

    if (activeSection === "hero") {
      setActiveLink(null);
    } else {
      setActiveLink(`#${activeSection}`);
    }
  };

  const allAnchors = document.querySelectorAll("a[href^='#']");
  allAnchors.forEach(anchor => {
    anchor.addEventListener("click", () => {
      const href = anchor.getAttribute("href");
      if (href && navLinkMap[href]) {
        setActiveLink(href);
      }
      mobileMenu.classList.add("hidden");
    });
  });

  window.addEventListener("scroll", updateSectionActive, { passive: true });
  window.addEventListener("resize", updateSectionActive);
  updateSectionActive();
}

let carouselInterval = null;

function initCarousel() {
  const track = document.getElementById("carousel-track");
  const slides = document.querySelectorAll(".carousel-slide");
  const dots = document.querySelectorAll(".carousel-dot");
  const currentText = document.getElementById("carousel-current");
  const totalText = document.getElementById("carousel-total");

  if (!track || slides.length === 0) return;

  let index = 0;
  const total = slides.length;

  if (totalText) totalText.textContent = total;

  function updateCarousel() {
    track.style.transform = `translateX(-${index * 100}%)`;

    dots.forEach((dot, i) => {
      if (i === index) {
        dot.style.width = "20px";
        dot.style.background = "#44D5E8";
      } else {
        dot.style.width = "6px";
        dot.style.background = "rgba(255,255,255,0.2)";
      }
    });

    if (currentText) currentText.textContent = index + 1;
  }

  function move(direction) {
    index = (index + direction + total) % total;
    updateCarousel();
  }

  function goTo(i) {
    index = i;
    updateCarousel();
  }


  window.carouselMove = move;
  window.carouselGoTo = goTo;


  if (carouselInterval) {
    clearInterval(carouselInterval);
  }

  carouselInterval = setInterval(() => {
    move(1);
  }, 1500);


  if (!window.__carouselResizeBound) {
    window.addEventListener("resize", updateCarousel);
    window.__carouselResizeBound = true;
  }

  updateCarousel();
}

loadSection("navbar", "navbar.html", initNavbar);
loadSection("hero", "hero.html", initCarousel);
loadSection("features", "features.html");
loadSection("about", "about.html");
loadSection("cta", "cta.html");
loadSection("footer", "footer.html");