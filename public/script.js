// Menu mobile
const navToggle = document.querySelector(".nav-toggle");
const nav = document.querySelector(".nav");

if (navToggle && nav) {
    navToggle.addEventListener("click", () => {
        const open = nav.classList.toggle("is-open");
        navToggle.setAttribute("aria-expanded", open);
        navToggle.querySelector(".icon").textContent = open ? "close" : "menu";
    });
}

// Bascule Dépense / Revenu : affiche les catégories du bon type
document.querySelectorAll("form").forEach((form) => {
    const radios = form.querySelectorAll('input[name="type"]');
    if (!radios.length) return;

    const update = () => {
        const type = form.querySelector('input[name="type"]:checked').value;
        form.dataset.type = type;
        form.querySelectorAll("[data-type-field]").forEach((field) => {
            const active = field.dataset.typeField === type;
            field.hidden = !active;
            field.querySelector("select").disabled = !active;
        });
        const submit = form.querySelector("[data-submit-label]");
        if (submit) {
            submit.querySelector(".label").textContent = type === "income" ? "Ajouter le revenu" : "Ajouter la dépense";
        }
    };
    radios.forEach((radio) => radio.addEventListener("change", update));
    update();
});

// Confirmation avant les actions destructives
document.querySelectorAll("[data-confirm]").forEach((el) => {
    el.addEventListener("click", (event) => {
        if (!confirm(el.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Les messages de succès disparaissent tout seuls
document.querySelectorAll(".alert-success").forEach((el) => {
    setTimeout(() => el.classList.add("is-hidden"), 4000);
});
