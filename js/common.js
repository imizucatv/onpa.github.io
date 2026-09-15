//マウスオーバーで画像切り替え
//start
$(function(){
    $('a img').hover(function(){
        $(this).attr('src', $(this).attr('src').replace('_off', '_on'));
    }, function(){
        if (!$(this).hasClass('currentPage')) {
            $(this).attr('src', $(this).attr('src').replace('_on', '_off'));
        }
    });
});
//end

//PCスマホで画像切り替え
$(function() {
    // 置換の対象とするclass属性。
    var $elem = $('img');
    // 置換の対象とするsrc属性の末尾の文字列。
    var sp = '_sp.';
    var pc = '_pc.';
    // 画像を切り替えるウィンドウサイズ。
    var replaceWidth = 768;

    function imageSwitch() {
        // ウィンドウサイズを取得する。
        var windowWidth = parseInt($(window).width());

        // ページ内にあるすべての`.js-image-switch`に適応される。
        $elem.each(function() {
            var $this = $(this);
            // ウィンドウサイズが768px以上であれば_spを_pcに置換する。
            if(windowWidth >= replaceWidth) {
                $this.attr('src', $this.attr('src').replace(sp, pc));

                // ウィンドウサイズが768px未満であれば_pcを_spに置換する。
            } else {
                $this.attr('src', $this.attr('src').replace(pc, sp));
            }
        });
    }
    imageSwitch();

    // 動的なリサイズは操作後0.2秒経ってから処理を実行する。
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            imageSwitch();
        }, 200);
    });
});

//スムーズスクロール
$(function(){
    $("a.js-scroll").click(function(){
        $('html,body').animate({ scrollTop: $($(this).attr("href")).offset().top }, 'slow','swing');
        return false;
    })
});

//ページトップ
$(function() {
    var topBtn = $('.js-pageTop');
    topBtn.hide();
    //スクロールが100に達したらボタン表示
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            topBtn.fadeIn();
        } else {
            topBtn.fadeOut();
        }
    });
    //スクロールしてトップ
    topBtn.click(function () {
        $('body,html').animate({
            scrollTop: 0
        }, 'slow','swing');
        return false;
    });
});

//アコーディオン
$(function(){
    $(document).on("click", ".js-acc", function(){
        $(this).next().stop(false,true).slideToggle();
        $(this).toggleClass("is-active");
    });
});

//タブ
$(function(){
    $(".js-tabHead li a").on("click", function() {
        var tabArea = $(this).parents('.js-tabArea');
        tabArea.find(".js-tabBox").hide();
        $($(this).attr("href")).fadeToggle();
        tabArea.find(".js-tabHead").find('a').removeClass("is-active");//追加部分
        $(this).toggleClass("is-active");//追加部分
        return false;
    });
});

//バリデーション
$(function(){
    $('.js-validation').validationEngine('attach', {
        promptPosition:"bottomLeft",
        scroll: true
    });
});


$(function() {
    $('.tab-list li').click(function() {
        var index = $('.tab-list li').index(this);
        $('.point-area .tab-cont').css('display','none');
        $('.point-area .tab-cont').eq(index).fadeIn("slow");
        $('.tab-list li').removeClass('select');
        $(this).addClass('select')
    });
});

 //アコーディオン
 jQuery(function($){
    $('.acMenu').on("click", function() {
    $(this).toggleClass("open");
    $(this).next().slideToggle(600);
    });
  });