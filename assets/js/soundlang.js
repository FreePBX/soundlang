var deleteCustoms = [],
files = [];

$(document).ready(function() {
	$('#formats').multiselect({
	});
});

$(document).on("click", 'button[id^="licenselink"]', function(){
	var langid = $(this).data('langid');
	$("#langid").val(langid);
	var langid = $("#langid").val();

	if ($(this).children('i').hasClass('fa-download') || $(this).children('i').hasClass('fa-level-up'))
	{
		var post_data = {
			module:  "soundlang",
			command: "licenseText",
			lang: 	 langid,
		};
		$.post(window.FreePBX.ajaxurl, post_data, function(data)
		{
			if (data.status)
			{
				$("#licensetext").text(data.license);

				$("#licensesub").attr("disabled", false);
				$("#licensesub").html(_("Accept License Agreement"));
				// $("#button_reload").show();
			}
			else {
				fpbxToast(data.message, '', 'error');
			}
		})
		.fail(function(xhr, textStatus, errorThrown) {
			fpbxToast(xhr.responseText, '', "error");

			$("#licensesub").attr("disabled", true);
			$("#licensesub").html(_("License Could Not be Retrieved"));

			console.dir(xhr);
			console.log(status);
			console.log(e);
		});
	}
	else if ($(this).children('i').hasClass('fa-trash-o'))
	{
		fpbxConfirm(
			sprintf(_('Are you sure you want to uninstall the "%s" language audio pack?'), langid),
			_("Yes"), _("No"),
			function() {
				// fpbxToast(_("Uninstalling. Please wait for the table to refresh"), '', "info");
				var post_data = {
					module:  "soundlang",
					command: "uninstall",
					lang: 	 langid,
				};
				$.post(window.FreePBX.ajaxurl, post_data, function(data)
				{
					fpbxToast(data.message, '', (data.status ? 'success' : 'error') );
					if (data.status) {
						$("#button_reload").show();
					}
				})
				.fail(function(xhr, textStatus, errorThrown) {
					fpbxToast(xhr.responseText, '', "error");
				})	
				.always(function() {
					soundLangPackagesTableRefresh();
				});
			}
		);
	}
});


function close_module_actions() {
	$('#langdialogwrapper').dialog('close');
}

$("#licensesub").on("click", function(){
	var button = $(this);
	button.html(_('Installing'));
	button.attr("disabled", true);
	var langid = $("#langid").val();

	var timer = null;
	
	content_data = $.param({
		module:  "soundlang",
		command: "installlang",
		"lang":  langid,
	});
	
	var boxInstall = $('<div id="langdialogwrapper"></div>')
	.dialog({
		title: _("Installing Package"),
		resizable: false,
		modal: true,
		width: "50%",
		height: "325",
		dialogClass: "no-close",
		open: function (e) {
			$('#licensemodal').modal('hide');

			$('#langdialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
			var xhr = new XMLHttpRequest();
			xhr.open('POST', FreePBX.ajaxurl, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(content_data);
			timer = window.setInterval(function() {
				if (xhr.readyState == XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
				}
				if (xhr.responseText.length > 0) {
					if ($('#langdialogwrapper').html().trim() != xhr.responseText.trim()) {
						$('#langdialogwrapper').html(xhr.responseText);
						$('#langprogress').animate({scrollTop: $(this).height()}, 500);
					}
				}
				if (xhr.readyState == XMLHttpRequest.DONE) {
					$("#langprogress").css("overflow", "auto");
					$("#langBoxContents button").focus();
					$('#langdialogwrapper').animate({scrollTop: $(this).height()}, 500);
				}
			}, 500);
		},
		close: function(e) {
			window.clearTimeout(timer);
			$(e.target).dialog("destroy").remove();
			$("#button_reload").show();
			soundLangPackagesTableRefresh();
		}
	});


	// ** Old installation mode, it installs in the background. **
	// var post_data = {
	// 	module:  "soundlang",
	// 	command: "install",
	// 	lang: 	 langid,
	// };
	// $.post(window.FreePBX.ajaxurl, post_data, function(data)
	// {
	// 	fpbxToast(data.message, '', (data.status ? 'success' : 'error') );
	// 	button.html(data.message);
	// 	$('#licensemodal').modal('hide');
	// })
	// .fail(function(xhr, textStatus, errorThrown) {
	// 	fpbxToast(xhr.responseText, '', "error");
	// 	console.dir(xhr);
	// 	console.log(status);
	// 	console.log(e);
	// })	
	// .always(function() {
	// 	soundLangPackagesTableRefresh();
	// });
});

$(".btn-remove").click(function() {
	var type = $(this).data("type"), btn = $(this), section = $(this).data("section");
	var chosen = $("#table-"+section).bootstrapTable("getSelections");
	$(chosen).each(function(){
		deleteCustoms.push(this['id']);
	});
	if(confirm(sprintf(_("Are you sure you wish to delete this %s?"),type))) {
		btn.find("span").text(_("Deleting..."));
		btn.prop("disabled", true);
		$.post( window.FreePBX.ajaxurl, {command: "delete", module: "soundlang", customlangs: deleteCustoms, type: type}, function(data) {
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
		url: window.FreePBX.ajaxurl,
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


/**
 * Drag/Drop/Upload Files
 */
$('#dropzone').on('drop dragover', function (e) {
	e.preventDefault();
});
$('#dropzone').on('dragleave drop', function (e) {
	$(this).removeClass("activate");
});
$('#dropzone').on('dragover', function (e) {
	$(this).addClass("activate");
});
$('#fileupload').fileupload({
	dataType: 'json',
	dropZone: $("#dropzone"),
	add: function (e, data) {
		//TODO: Need to check all supported formats
		var sup = "\.("+supportedRegExp+")$",
				patt = new RegExp(sup),
				submit = true;
		$.each(data.files, function(k, v) {
			if(!patt.test(v.name.toLowerCase())) {
				submit = false;
				alert(_("Unsupported file type"));
				return false;
			}
			var s = v.name.replace(/\.[^/.]+$/, "").replace(/\s|&|<|>|\.|`|'|\*|\?|\"/g, '-').toLowerCase();
		});
		if(submit) {
			data.submit();
		}
	},
	drop: function () {
		$("#upload-progress .progress-bar").css("width", "0%");
	},
	dragover: function (e, data) {
	},
	change: function (e, data) {
	},
	done: function (e, data) {
		if(data.result.status) {
			if(data.result.gfiles.length === 0) {
				alert(_("No Suitable files were found in the archive!"));
				return;
			}
			$.each(data.result.gfiles, function(k,v) {
				files.push(v);
			});
			$("#filecount").text(files.length);
			if(data.result.bfiles.length > 0) {
				//alert("Some files were not suitable to be uploaded. Check the console log for more information")
				console.warn("The below files are NOT supported");
				console.warn(data.result.bfiles);
			}
		} else {
			alert(data.result.message);
		}
	},
	progressall: function (e, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		$("#upload-progress .progress-bar").css("width", progress+"%");
	},
	fail: function (e, data) {
	},
	always: function (e, data) {
	}
});
$("#customlang-frm").submit(function(e) {
	e.preventDefault();
	$("#action-buttons input").prop("disabled",true);
	var data = {};
	if($("#language").val().trim() === "") {
		return warnInvalid($("#language"),_("You must set a valid language code"));
	}
	data.language = $("#language").val().trim();

	data.id = $("#id").val();

	data.description = $("#description").val();

	data.codecs = [];
	$(".codec:checked").each(function() {
		data.codecs.push($(this).val());
	});

	if(data.codecs.length > 0 && !confirm(_("If you are doing media conversions this can take a very long time, is that ok?"))) {
		$("#action-buttons input").prop("disabled",false);
		return;
	}
	var process = [], playback = [], remove = [];
	if(data.codecs.length > 0) {
		$.each(files, function(file, d) {
			$.each(data.codecs, function(k, codec) {
				process.push({
					"name": d.filename,
					"codec": codec,
					"temporary": d.localfilename,
					"language": data.language,
					"directory": d.directory
				});
			});
		});
	} else {
		$.each(files, function(file, d) {
			process.push({
				"name": d.filename,
				"codec": "",
				"temporary": d.localfilename,
				"language": data.language,
				"directory": d.directory
			});
		});
	}
	if(process.length > 0) {
		var total = process.length, count = 0;
		$("#recscreen .progress-bar").prop("aria-valuenow",0);
		$("#recscreen .progress-bar").css("width",0+"px");
		$("#recscreen").removeClass("hidden");
		var temps = [];
		async.forEachOfSeries(process, function (value, key, callback) {
			value.command = "convert";
			value.module = "soundlang";
			if(value.codec !== "") {
				$("#recscreen label").html(sprintf(_("Processing %s for %s in format %s"),value.name, data.language, value.codec));
			} else {
				$("#recscreen label").html(sprintf(_("Copying %s to %s"),value.name, data.language));
			}
			$.ajax({
				type: 'POST',
				url: window.FreePBX.ajaxurl,
				data: value,
				dataType: 'json',
				timeout: 240000
			}).done(function(data) {
				if(data.status) {
					temps.push(value.temporary);
					callback();
				} else {
					fpbxToast(data.message,'Error','error');
					console.error(data);
					callback(data.message);
				}
			}).fail(function(data) {
				console.error(data);
				callback(data);
			}).always(function(data) {
				count++;
				var progress = (count/total) * 100;
				$("#recscreen .progress-bar").prop("aria-valuenow",progress);
				$("#recscreen .progress-bar").css("width",progress+"%");
			});
		}, function(err){
			if(err) {
				alert(_("There was an error, See the console for more details"));
				console.error(err);
				$("#action-buttons input").prop("disabled", false);
				$("#recscreen").addClass("hidden");
			} else {
				$("#recscreen label").text(_("Deleting Temporary Files..."));
				$.ajax({
					type: 'POST',
					url: window.FreePBX.ajaxurl,
					data: {"module":"soundlang","command":"deletetemps","temps":temps},
					dataType: 'json',
					timeout: 240000
				}).done(function(d) {
					if(d.status) {
						$("#recscreen label").text(_("Finished!"));
						saveCustomLang(data.id, data.language, data.description);
					} else {
						alert(d.message);
					}
				}).fail(function(data) {
					alert(data);
				});
			}
		});
	} else {
		$("#recscreen label").text(_("Finished!"));
		saveCustomLang(data.id, data.language, data.description);
	}
});
function saveCustomLang(id, language, description) {
	$.post( window.FreePBX.ajaxurl, {module: "soundlang", command: "saveCustomLang", id: id, language: language, description: description}, function( data ) {
		if(data.status) {
			window.location = "?display=soundlang&action=customlangs";
		} else {
			alert(data.message);
			$("#recscreen").addClass("hidden");
			$("#action-buttons input").prop("disabled", false);
		}
	});
}







function getSoundLangPackagesTable() {
	return $('#soundlang-packages-list');
}

function getSoundLangPackagesLangTable() {
	return $('#soundlang-packages-lang-list');
}

$(document).on("click", 'button[id^="refreshshowall"]',function(){
	soundLangPackagesTableRefreshAllPackages();
});

$(document).on("click", 'button[id^="refreshshowinstalled"]',function(){
	soundLangPackagesTableRefreshInstalledPackages();
});


$(document).on("click", 'button[id^="btnUpdatePackages"]',function(){
	boxPleaseWait(true);
	var post_data = {
		module:  "soundlang",
		command: "updatepackages",
	};
	$.post(window.FreePBX.ajaxurl, post_data, function(data)
	{
		fpbxToast(data.message, '', (data.status ? 'success' : 'error') );
	})
	.fail(function(xhr, textStatus, errorThrown) {
		fpbxToast(xhr.responseText, '', "error");
	})	
	.always(function() {
		soundLangPackagesTableRefreshAllPackages();
	});
});

function boxPleaseWait(status)
{
	if (status == true) { $("#box_please_wait").show(); }
	else 				{ $("#box_please_wait").hide(); }
}

getSoundLangPackagesTable().on('load-success.bs.table load-error.bs.table', function () {
	boxPleaseWait(false);
});

// getSoundLangPackagesTable().on('refresh.bs.table', function () {
// 	boxPleaseWait(true);
// });

function soundLangPackagesTableRefresh(){
	getSoundLangPackagesTable().bootstrapTable('refresh');
}

function soundLangPackagesTableRefreshAllPackages(){
	getSoundLangPackagesTable().bootstrapTable('refresh', {url: window.FreePBX.ajaxurl +'?module=soundlang&command=packages&tabledata=yes&showall=yes'});
	$("#refreshshowall").hide();
	$("#refreshshowinstalled").show();
}

function soundLangPackagesTableRefreshInstalledPackages(){
	getSoundLangPackagesTable().bootstrapTable('refresh', {url: window.FreePBX.ajaxurl + '?module=soundlang&command=packages&tabledata=yes'});
	$("#refreshshowall").show();
	$("#refreshshowinstalled").hide();
}

function soundLangPackagesColName (value, row, index)
{
	var html = sprintf('<button type="button" class="btn btn-primary" name="langmodal" data-langid="%s"><i class="fa fa-info fa-lg" aria-hidden="true"></i> > <i class="fa fa-file-audio-o fa-lg" aria-hidden="true"></i></button> %s', row.lang, value);
	return html;
}

function soundLangPackagesColAuthor (value, row, index)
{
	var html = "";
	if (row.authorlink === "") 	{ html = value; }
	else 						{ html = sprintf('<a href="%s" target="#">%s</a>', row.authorlink, value); }
	return html;
}

function soundLangPackagesColActions (value, row, index)
{
	var html = "";
	if (row.isUpdated) {
		html = '<button type="button" class="btn btn-success btn-block" data-toggle="modal" data-langid="' + row.lang + '" data-target="#licensemodal" id="licenselink' + row.lang + '" data-licenselink="' + row.license + '" class="clickable"><i class="fa fa-level-up fa-fw"></i></button>';
	}
	else if (row.installed)
	{
		html = '<button type="button" class="btn btn-danger btn-block" data-langid="' + row.lang + '" id="licenselink'+ row.lang +'"><i class="fa fa-trash-o fa-fw"></i></button>';
	}
	else
	{
		html = '<button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-langid="' + row.lang + '" data-target="#licensemodal" id="licenselink' + row.lang + '" data-licenselink="' + row.license + '" class="clickable"><i class="fa fa-download fa-fw"></i></button>';
	}
	return html;
}

$("#formSoundLangSettings").submit(function(event)
{
    event.preventDefault();

	let frm_lang 	= $("#language").val();
	let frm_formats = $("#formats").val();
	boxPleaseWait(true);
	var post_data = {
		module		: "soundlang",
		command		: "savesettings",
		language	: frm_lang,
		formats		: frm_formats,
		// runinstall	: 'yes', //It does not show a window with the update process, only the one to wait please.
	};

	$.post(window.FreePBX.ajaxurl, post_data, function(data)
	{
		console.log(data);
		if (data.runinstall || !data.status)
		{
			fpbxToast(data.message, '', (data.status ? 'success' : 'error') );
		}
		else 
		{
			SettingsUpdatePackeges(data.message);
		}
	})
	.fail(function(xhr, textStatus, errorThrown) {
		fpbxToast(xhr.responseText, '', "error");

		console.dir(xhr);
		console.log(status);
		console.log(e);
	})
	.always(function() {
		boxPleaseWait(false);
	});
});

function SettingsUpdatePackeges(message)
{
	var timer 		 = null;
	var content_data = $.param({
		module	: "soundlang",
		command	: "updatelang",
	});
	var boxInstall 	= $('<div id="langdialogwrapper"></div>')
	.dialog({
		title: _("Updating Package"),
		resizable: false,
		modal: true,
		width: "50%",
		height: "325",
		dialogClass: "no-close",
		open: function (e) {
			$('#langdialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
			var xhr = new XMLHttpRequest();
			xhr.open('POST', FreePBX.ajaxurl, true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.send(content_data);
			timer = window.setInterval(function() {
				if (xhr.readyState == XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
				}
				if (xhr.responseText.length > 0) {
					if ($('#langdialogwrapper').html().trim() != xhr.responseText.trim()) {
						$('#langdialogwrapper').html(xhr.responseText);
						$('#langprogress').animate({scrollTop: $(this).height()}, 500);
					}
				}
				if (xhr.readyState == XMLHttpRequest.DONE) {
					$("#langprogress").css("overflow", "auto");
					$("#langBoxContents button").focus();
					$('#langdialogwrapper').animate({scrollTop: $(this).height()}, 500);
					fpbxToast(message, '', 'success');
				}
			}, 500);
		},
		close: function(e) {
			window.clearTimeout(timer);
			$(e.target).dialog("destroy").remove();
			$("#button_reload").show();
		}
	});
}

$(document).on('click', 'button[name^="langmodal"]', function() {
	var langid = $(this).data('langid');
	getSoundLangPackagesLangTable().bootstrapTable('refresh', {
		url: window.FreePBX.ajaxurl + "?module=soundlang&command=packagesLang&lang=" + langid,
	});
	$("#langmodal").modal("show");
});