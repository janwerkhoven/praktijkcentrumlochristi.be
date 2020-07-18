const isProduction =
  location.host === "www.praktijkcentrumlochristi.be" ? true : false;
const environment = isProduction ? "production" : "development";

// Fire page view to Google Analytics
if (ga) {
  ga("create", "UA-26179509-5", "auto");
  ga("set", {
    dimension1: environment,
  });
  ga("send", "pageview");
}

$(document).ready(function () {
  // Mobile navigation

  $("header nav>ul>li")
    .not(":first-child")
    .not(":last-child")
    .children("a")
    .on("click", function (e) {
      e.preventDefault();
      var i = $("header nav>ul>li").index($(this).parent("li"));
      $("html, body").animate(
        {
          scrollTop: $("footer nav>ul>li").eq(i).offset().top,
        },
        1000
      );
    });
});

document.addEventListener("DOMContentLoaded", () => {
  init();
});

function init() {
  prepareBookModal();
}

function prepareBookModal() {
  const bookButtons = document.querySelectorAll("button.book");
  const yesButtons = document.querySelectorAll("#modal button.yes");
  const overlay = document.querySelector("#modal .overlay");
  const noButton1 = document.querySelector(`#modal #question-1 button.no`);
  const noButton2 = document.querySelector(`#modal #question-2 button.no`);
  const noButton3 = document.querySelector(`#modal #question-3 button.no`);

  bookButtons.forEach((button) => {
    button.onclick = function () {
      openModal();
    };
  });

  yesButtons.forEach((button) => {
    button.onclick = function () {
      showModalContent("call-us");
    };
  });

  noButton1.onclick = () => {
    showModalContent("question-2");
  };

  noButton2.onclick = () => {
    showModalContent("question-3");
  };

  noButton3.onclick = () => {
    showModalContent("book-online");
  };

  overlay.onclick = () => {
    closeModal();
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
