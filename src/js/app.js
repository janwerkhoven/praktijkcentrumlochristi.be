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

$(document).ready(function() {
  // Mobile navigation

  $("header nav>ul>li")
    .not(":first-child")
    .not(":last-child")
    .children("a")
    .on("click", function(e) {
      e.preventDefault();
      var i = $("header nav>ul>li").index($(this).parent("li"));
      $("html, body").animate(
        {
          scrollTop: $("footer nav>ul>li")
            .eq(i)
            .offset().top
        },
        1000
      );
    });
});
