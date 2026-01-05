const header = document.querySelector(".site-header");
const navLinks = document.querySelectorAll(".navbar .nav-link");
const logoTrack = document.getElementById("logoTrack");
const testimonialItems = document.querySelectorAll(".testimonial-item");
const prevButton = document.getElementById("prevTestimonial");
const nextButton = document.getElementById("nextTestimonial");

let currentTestimonial = 0;
let testimonialTimer;

const toggleHeaderShadow = () => {
  if (window.scrollY > 40) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
};

toggleHeaderShadow();
window.addEventListener("scroll", toggleHeaderShadow);

navLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    const targetId = link.getAttribute("href");
    if (targetId && targetId.startsWith("#")) {
      event.preventDefault();
      const target = document.querySelector(targetId);
      if (target) {
        window.scrollTo({
          top: target.offsetTop - 80,
          behavior: "smooth",
        });
      }
    }
  });
});

const updateTestimonial = (index) => {
  testimonialItems.forEach((item, itemIndex) => {
    item.classList.toggle("active", itemIndex === index);
  });
};

const nextTestimonial = () => {
  currentTestimonial = (currentTestimonial + 1) % testimonialItems.length;
  updateTestimonial(currentTestimonial);
};

const prevTestimonial = () => {
  currentTestimonial =
    (currentTestimonial - 1 + testimonialItems.length) % testimonialItems.length;
  updateTestimonial(currentTestimonial);
};

const resetTimer = () => {
  clearInterval(testimonialTimer);
  testimonialTimer = setInterval(nextTestimonial, 6000);
};

if (testimonialItems.length > 1) {
  resetTimer();

  if (prevButton && nextButton) {
    prevButton.addEventListener("click", () => {
      prevTestimonial();
      resetTimer();
    });

    nextButton.addEventListener("click", () => {
      nextTestimonial();
      resetTimer();
    });
  }
}

const duplicateLogos = () => {
  if (!logoTrack) {
    return;
  }
  const logos = Array.from(logoTrack.children);
  if (logos.length === 0) {
    return;
  }
  logos.forEach((logo) => {
    const clone = logo.cloneNode(true);
    logoTrack.appendChild(clone);
  });
  const duration = Math.max(18, logos.length * 3);
  logoTrack.style.animationDuration = `${duration}s`;
};

duplicateLogos();
