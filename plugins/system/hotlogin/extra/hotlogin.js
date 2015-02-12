window.addEvent('domready', function() {
	var hldiv = $('HLwrapper');
    var ua = navigator.userAgent;
    if (ua.indexOf('MSIE') == -1) { hldiv.setStyles( {'height':1} ); }
	hldiv.setProperty('open', false);
	var invisible_h=document.getElementById("HLhidden").offsetHeight-hoffset;
	var trigger = $('HLtrigger');
	var ani = new Fx.Style(hldiv, 'margin-top', {
		duration: 700,
		wait: false,
		onComplete: function() {
						state=hldiv.getProperty('open');
						if (state=='true') {
							state='false';
						} else {
							state='true';
						}
						hldiv.setProperty('open', state);
					}
		});
	ani.options.transition = Fx.Transitions.Cubic.easeOut;
	hldiv.setStyles({'margin-top':-(invisible_h)});
    hldiv.setOpacity(HLopacity);
	trigger.addEvent('click', function(event){
		if (hldiv.getProperty('open')=='true') {
            var invisible_h=document.getElementById("HLhidden").offsetHeight-hoffset;
			ani.start(-invisible_h);
			} else {
			ani.start(0);
		}
	});
});
