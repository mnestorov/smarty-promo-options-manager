jQuery(document).ready(function($) {
    // Handle tab switching
    $(".smarty-po-nav-tab").click(function (e) {
        e.preventDefault();
        $(".smarty-po-nav-tab").removeClass("smarty-po-nav-tab-active");
        $(this).addClass("smarty-po-nav-tab-active");

        $(".smarty-po-tab-content").removeClass("active");
        $($(this).attr("href")).addClass("active");
    });

    // Load README.md
    $("#smarty-po-load-readme-btn").click(function () {
        const $content = $("#smarty-po-readme-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyPromoOptionsManager.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_po_load_readme",
                nonce: smartyPromoOptionsManager.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading README.md</p>");
                }
            },
        });
    });

    // Load CHANGELOG.md
    $("#smarty-po-load-changelog-btn").click(function () {
        const $content = $("#smarty-po-changelog-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyPromoOptionsManager.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_po_load_changelog",
                nonce: smartyPromoOptionsManager.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading CHANGELOG.md</p>");
                }
            },
        });
    });
});
