var deleteCustoms = [];
$(".btn-remove").click(function() {
	var type = $(this).data("type"), btn = $(this), section = $(this).data("section");
	var chosen = $("#table-"+section).bootstrapTable("getSelections");
	$(chosen).each(function(){
		deleteCustoms.push(this['id']);
	});
	if(confirm(sprintf(_("Are you sure you wish to delete this %s?"),type))) {
		btn.find("span").text(_("Deleting..."));
		btn.prop("disabled", true);
		$.post( "ajax.php", {command: "delete", module: "soundlang", customlangs: deleteCustoms, type: type}, function(data) {
			if(data.status) {
				btn.find("span").text(_("Delete"));
				$("#table-"+section).bootstrapTable('remove', {
					field: "id",
					values: deleteCustoms
				});
			} else {
				btn.find("span").text(_("Delete"));
				btn.prop("disabled", true);
				alert(data.message);
			}
		});
	}
});
$("table").on("page-change.bs.table", function () {
	$(".btn-remove").prop("disabled", true);
	deleteCustoms = [];
});
$("table").on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
	var toolbar = $(this).data("toolbar"), button = $(toolbar).find(".btn-remove"), buttone = $(toolbar).find(".btn-send"), id = $(this).prop("id");
	button.prop('disabled', !$("#"+id).bootstrapTable('getSelections').length);
	buttone.prop('disabled', !$("#"+id).bootstrapTable('getSelections').length);
	deleteCustoms = $.map($("#"+id).bootstrapTable('getSelections'), function (row) {
		return row.extension;
  });
});
//Trashcan Action
$(document).on("click", 'a[id^="del"]',function(){
	var cmessage = _("Are you sure you want to delete this custom language?");
	if(!confirm(cmessage)){
		return false;
	}
	var uid = $(this).data('uid');
	var row = $('#row'+uid);
	$.ajax({
		url: "/admin/ajax.php",
		data: {
			module:'soundlang',
			command:'delete',
			customlangs:[uid],
			type:'customlangs'
		},
		type: "GET",
		dataType: "json",
		success: function(data){
			if(data.status === true){
				row.fadeOut(2000,function(){
					$(this).remove();
				});
			}else{
				warnInvalid(row,data.message);
			}
		},
		error: function(xhr, status, e){
			console.dir(xhr);
			console.log(status);
			console.log(e);
		}
	});
});
