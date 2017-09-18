$(function() {
	$('#toc').tableOfContents(null, {
		depth: 3
	});
	
	$('#toc a').each(function() {
		var re = new RegExp('(>)'+$(this).text()+'(<)', 'g');
		var html = $('#documentation').html().replace(re, '$1<a href="'+this.href+'">'+$(this).text()+'</a>$2');
		$('#documentation').html(html);
	});
	
	var ymap = [];
	
	$('h1, h2, h3, h4, h5, h6').each(function() {
		$(this).html($(this).text());
	});
	
	$('h1, h2, h3').each(function() {
		ymap.push([$(this).offset().top, this.id]);
	});
	
	$('#toc a').click(function() {
		$(window).scroll();
	});
	
	$(window).scroll(function() {
		var st = $(this).scrollTop();
		for(var i = ymap.length - 1; i >= 0; i--) {
			if(st > ymap[i][0] - 20) {
				
				$('#toc .active').removeClass('active');
				$('#toc a[href=#'+ymap[i][1]+']').addClass('active');
				
				break;
			}
		}
	});
	
});
