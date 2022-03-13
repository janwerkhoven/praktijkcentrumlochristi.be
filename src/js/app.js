document.addEventListener("DOMContentLoaded", () => {
  init();
});

function init() {
  prepareBookModal();
  fireGoogleAnalytics();
}

function prepareBookModal() {
  document.querySelectorAll("button.book").forEach((button) => {
    button.onclick = function () {
      openModal();
    };
  });

  document.querySelector("#modal .overlay").onclick = () => {
    closeModal();
  };

  document.querySelector(`#modal #question-1 button.yes`).onclick = () => {
    showModalContent("infectious");
  };

  document.querySelector(`#modal #question-1 button.no`).onclick = () => {
    showModalContent("not-infectious");
  };
}

function openModal() {
  document.body.classList.add("show-modal");
  document.querySelector("#page").style.top = `-${window.scrollY}px`;
  showModalContent("question-1");
}

function closeModal() {
  document.body.classList.remove("show-modal");
  document.querySelector("#page").removeAttribute("style");
  document.querySelectorAll("#modal section").forEach((element) => {
    element.classList.add("hide");
  });
}

function showModalContent(id) {
  document.querySelector(`#modal section#${id}`).classList.remove("hide");
  document.querySelectorAll(`#modal section:not(#${id})`).forEach((element) => {
    element.classList.add("hide");
  });
}

function fireGoogleAnalytics() {
  if (ga) {
    const environment =
      location.host === "www.praktijkcentrumlochristi.be"
        ? "production"
        : "development";

    ga("create", "UA-26179509-5", "auto");
    ga("set", {
      dimension1: environment,
    });
    ga("send", "pageview");
  }
}
