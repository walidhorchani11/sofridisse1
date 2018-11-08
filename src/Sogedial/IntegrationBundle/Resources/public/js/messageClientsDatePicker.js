jQuery(document).ready(function ($) {
    var href = $(this).attr('href');
    $.datepicker.setDefaults($.datepicker.regional["fr"]);

    var datepicker1Config = {
        dateFormat: 'dd/mm/yy'
    };

    var datepicker2Config = {
        dateFormat: 'dd/mm/yy'
    };

    $('.js-datepicker1').datepicker(datepicker1Config);
    $('.js-datepicker2').datepicker(datepicker2Config);

    $('#js-datepicker1 input').change(function(){
        $(this).removeClass("input-empty");
        setTimeout(function() {
            $('.js-datepicker2').datepicker("show");         
        }, 0);
    })

    $('#js-datepicker2 input').change(function(){
        $(this).removeClass("input-empty");
    })
    $(window).resize(function () {
        var input = $(".dry-date")[0];
        var inst = $.datepicker._getInst(input);
        var pos = $.datepicker._findPos(input);
        pos[1] += input.offsetHeight;
        var offset = offset = {left: pos[0], top: pos[1]};
        offset = $.datepicker._checkOffset(inst, offset, false);
        inst.dpDiv.css({left: offset.left + "px", top: offset.top + "px"});
    });


    /**
     * Stringify date (format: dd/mm/yyyy)
     */
    function stringifyDate(date) {
        if(date !== '' && typeof(date) !== 'undefined') {
            return date.replace(/\//g,'');
        }
        return '';
    }


});