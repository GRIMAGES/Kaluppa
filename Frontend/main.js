const menuBtn = document.getElementById("menu-btn");
const navLinks = document.getElementById("nav-links");
const menuBtnIcon = menuBtn.querySelector("i");

menuBtn.addEventListener("click", (e) => {
    navLinks.classList.toggle("open");

    const isOpen = navLinks.classList.contains("open");
    menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line")
});

navLinks.addEventListener("click", (e) =>{
    navLinks.classList.remove("open");
    menuBtnIcon.setAttribute("class", "ri-menu-line")
});

const scrollRevealOption = {
    distance: "50px",
    origin: "bottom",
    duration: 1000,
};

ScrollReveal().reveal(".container__left h2", {
    ...scrollRevealOption,
});
ScrollReveal().reveal(".container__left .container__btn", {
    ...scrollRevealOption,
    delay: 1000,
});
ScrollReveal().reveal(".container__left h3", {
    ...scrollRevealOption,
    delay: 1000, 
});
ScrollReveal().reveal(".container__left h1", {
    ...scrollRevealOption,
    delay: 500, 
});
ScrollReveal().reveal(".container__right h3", {
    ...scrollRevealOption,
    delay: 1000, 
});
ScrollReveal().reveal(".container__right p", {
    ...scrollRevealOption,
    delay: 3500, 
});

ScrollReveal().reveal(".container__right .KALUPPA", {
    duration: 1000,
    delay: 1000, 
});

ScrollReveal().reveal(".container__right .Graduates", {
    duration:1000,
    delay: 4500, 
});

ScrollReveal().reveal(".location", {
    ...scrollRevealOption,
    origin: "left",
    delay: 5000, 
});
ScrollReveal().reveal(".socials span", {
    ...scrollRevealOption,
    origin: "top",
    delay: 5500, 
    interval: 500,
});

document.querySelectorAll(".nav__links a").forEach(anchor => {
    anchor.addEventListener("click", function(event) {
        event.preventDefault();
        const targetId = this.getAttribute("href").substring(1);
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            window.scrollTo({
                top: targetSection.offsetTop - 80,
                behavior: "smooth"
            });
        }
    });
});
