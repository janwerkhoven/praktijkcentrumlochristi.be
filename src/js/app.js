const isProduction =
  location.host === "www.praktijkcentrumlochristi.be" ? true : false;
const environment = isProduction ? "production" : "development";

// Fire page view to Google Analytics
if (ga) {
  ga("create", "UA-26179509-5", "auto");
  ga("set", {
    dimension1: environment
  });
  ga("send", "pageview");
}

// $(document).ready(function() {
//   // Mobile navigation
//
//   $("header nav>ul>li")
//     .not(":first-child")
//     .not(":last-child")
//     .children("a")
//     .on("click", function(e) {
//       e.preventDefault();
//       var i = $("header nav>ul>li").index($(this).parent("li"));
//       $("html, body").animate(
//         {
//           scrollTop: $("footer nav>ul>li")
//             .eq(i)
//             .offset().top
//         },
//         1000
//       );
//     });
// });

function init() {
  console.log("init()");

  const openButton = document.querySelector("#landing button.open");
  const modalBackground = document.querySelector("#modal");
  const modalContent = document.querySelector("#modal .content");
  const questionContent = document.querySelector("#modal #questions.content");
  const bookContent = document.querySelector("#modal #book.content");
  const callContent = document.querySelector("#modal #call.content");
  const yesButtons = document.querySelectorAll("#questions button.yes");
  const noButtons = document.querySelectorAll("#questions button.no");

  yesButtons.forEach(function(yesButton) {
    console.log(yesButton);
    yesButton.onclick = function() {
      console.log("clicked yes");
      callContent.classList.add("shown");
      callContent.classList.remove("hidden");
      questionContent.classList.add("hidden");
      questionContent.classList.remove("shown");
    };
  });

  noButtons.forEach(function(noButton) {
    console.log(noButton);
    noButton.onclick = function() {
      console.log("clicked no");
      // bookContent.classList.add("shown");
      // bookContent.classList.add("hidden");
      // questionContent.classList.add("hidden");
      // questionContent.classList.remove("shown");
      noButton.classList.add("highlighted");
    };
  });

  modalContent.onclick = function() {
    console.log("user clicked modal content");
    event.stopPropagation();
  };

  if (modalBackground) {
    console.log("found modal");
    modalBackground.onclick = function() {
      closeModal();
    };
  } else {
    console.warn("could not find modal");
  }

  if (openButton) {
    console.log("button found");
    openButton.onclick = function() {
      console.log("user clicked");
      openModal();
    };
  } else {
    console.warn("could not find openButton!");
  }

  if (questionContent) {
    console.log("user clicked yes");
    questionContent.classList.remove("hidden");
    questionContent.classList.add("shown");
  }
}

function openModal() {
  console.log("open modal");
  const modal = document.querySelector("#modal");
  console.log(modal);
  modal.classList.add("open");
  modal.classList.remove("close");
}

function closeModal() {
  console.log("close modal");
  const modal = document.querySelector("#modal");
  const questionContent = document.querySelector("#modal #questions.content");
  const callContent = document.querySelector("#modal #call.content");
  modal.classList.remove("open");
  modal.classList.add("close");
  callContent.classList.add("shown");
  callContent.classList.remove("hidden");
  questionContent.classList.add("hidden");
  questionContent.classList.remove("shown");
}

document.addEventListener("DOMContentLoaded", function() {
  init();
});
// if (document.readystate == "loading") {
//   document.addEventListener("DOMcontentloaded", init);
// } else {
//   init();
// }
