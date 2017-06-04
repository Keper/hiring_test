//$(document).ready(function(){
	
	$('body').on('click', '.page', function(){
		
		var link; 
		
		link = $(this).html();
		
		if(isNaN(link)) {
			link = $(this).data('type');
		}		
//		console.log(link);
		
		$.ajax({
			
			async: false,
			url: "paginator",
			type: "POST",	
			dataType: "json",
			data: "link="+jQuery.trim(link),
			success: function(res){
				$('#pag_numbers').html(res.paginator);
				$('#table').html(res.table);	
			},
      error: function(err) {
        console.log(err);
      },
		
		});
		
	});

//});

