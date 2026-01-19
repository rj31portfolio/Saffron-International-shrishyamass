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

const contactForm = document.getElementById("contactForm");

if (contactForm) {
  contactForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(contactForm);
    const payload = Object.fromEntries(formData.entries());
    const submitButton = contactForm.querySelector("button[type=\"submit\"]");
    const requiredFields = ["name", "email", "subject", "message"];
    const missingField = requiredFields.find((field) => {
      return !payload[field] || String(payload[field]).trim() === "";
    });

    if (missingField) {
      const errorMessage = "Please fill out all required fields.";
      if (window.Swal) {
        Swal.fire("Missing info", errorMessage, "warning");
      } else {
        alert(errorMessage);
      }
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch("contact.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });
      const responseText = await response.text();
      let data = null;
      if (responseText) {
        try {
          data = JSON.parse(responseText);
        } catch (parseError) {
          throw new Error("Unexpected response from server.");
        }
      }
      if (!response.ok || !data || !data.success) {
        const serverMessage = data && data.message ? data.message : "Unable to send message.";
        const extraInfo = data && data.error ? ` (${data.error})` : "";
        throw new Error(serverMessage + extraInfo);
      }
      contactForm.reset();
      if (window.Swal) {
        Swal.fire("Sent!", "Your message has been sent successfully.", "success");
      } else {
        alert("Your message has been sent successfully.");
      }
    } catch (error) {
      if (window.Swal) {
        Swal.fire("Error", error.message || "Unable to send message.", "error");
      } else {
        alert("Unable to send message.");
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });
}
