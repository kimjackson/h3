/*
 *  Heurist blog functions
 *  Kim Jackson, July 2008
 */

var Blog = {

blogEntryRecordType: HRecordTypeManager.getRecordTypeById(137),
websiteRecordType: HRecordTypeManager.getRecordTypeById(1),
mediaRecordType: HRecordTypeManager.getRecordTypeById(74),

titleDetailType: HDetailManager.getDetailTypeById(160),
thumbnailDetailType: HDetailManager.getDetailTypeById(223),
geoDetailType: HDetailManager.getDetailTypeById(230),

monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],

records: [],

archivedEntries: {},

entries: [],

canEdit: function() {
	if (this.user) return (HCurrentUser === this.user);
	if (this.group) return (HCurrentUser.isInWorkgroup(this.group));
},

init: function(options) {
	Blog.user = options.user || null;
	Blog.group = options.user ? null : (options.group || null);
	Blog.type = options.type || null;
	Blog.query = options.query || null;

	if (! Blog.user  &&  ! (Blog.group  &&  HCurrentUser.isInWorkgroup(Blog.group))) {
		Comment.prototype.edit = Comment.prototype.reply = Comment.prototype.remove = function() {
			alert("Comments only available to workgroup members.\n" +
			      "For an open comment blog use a personal blog.");
		};
	}

	var bulkLoader = new HLoader (
		function(s, r, c) {	// onload
			Blog.records.push.apply(Blog.records, r);
			if (Blog.records.length < c) {	// we haven't got all the results yet
				HeuristScholarDB.loadRecords (
					new HSearch(s.getQuery() + " offset:"+Blog.records.length),
					bulkLoader
				);
			}
			else {
				// we've loaded all the records
				if (Blog.user  &&  ! Blog.canEdit()) {
					Blog.loadTags();
				} else {
					Blog.displayBlogEntries();
					Blog.displayTagList();
				}
			}
		},
		function(s,e) {	// onerror
			alert("error loading records: " + e);
		}
	);
	HeuristScholarDB.loadRecords (
		new HSearch("type:"+Blog.type.getID() + (Blog.query ? " "+Blog.query : (Blog.user ? " user:"+Blog.user.getID() : " workgroup:"+Blog.group.getID())) + " sortby:-a"),
		bulkLoader
	);
},

displayBlogEntries: function() {
	// some more initialisation to do - we had to wait until the records were loaded
	var now, initFilter;

	initFilter = YAHOO.util.History.getBookmarkedState("f");
	if (! initFilter) {
		//now = new Date();
		//initFilter = Blog.buildMonthString(now.getFullYear(), now.getMonth());
		initFilter = "";
	}

	YAHOO.util.History.register("f", initFilter, function(state) {
		Blog.setFilter(state);
	});

	YAHOO.util.History.onReady(function() {
		var state = YAHOO.util.History.getCurrentState("f");
		Blog.setFilter(state);
	});

	try {
		YAHOO.util.History.initialize("yui-history-field", "yui-history-iframe");
	} catch (e) {
		Blog.setFilter(initFilter);
	}

	Blog.displayArchives();
},

buildMonthString: function(year, month) {
	// month is Date-style (0 == Jan) whereas monthString is human-style (1 == Jan)
	return year + "-" + (month < 9 ? "0" : "") + (month + 1);
},

parseMonthString: function(monthString) {
	var year, month;
	year = monthString.substr(0,4) - 0;
	month = monthString.substr(5,2) - 1;
	return { "year" : year, "month": month };
},

setFilter: function(state) {
	var opts = null;

	state = state.split("/");
	if (state[0]) {
		opts = Blog.parseMonthString(state[0]);
	}
	if (state[1]) {
		opts = { "wgtag" : state[1] };
	}
	if (state[2]) {
		opts = { "tag" : state[2] };
	}

	Blog.search(opts);
},


BlogEntry: function(record, parentElement, isNew) {
	var that = this;
	this.record = record;
	this.$div = $("<div/>").addClass("entry");
	this.$outerTable = $("<table class='entry-outer-table'/>").appendTo(this.$div);
	this.$outerTbody = $("<tbody/>").appendTo(this.$outerTable);

	this.$outerTbody.append("<tr><td class='entry-left'></td><td class='entry-right'></td></tr>");
	this.$table = $("<table/>").appendTo($('td.entry-right', this.$outerTbody));
	var $tbody = $("<tbody/>").appendTo(this.$table);

	$tbody.append("<tr><td></td><td><h3/></td></tr>");
	$("h3", $tbody).addClass("entry-date").text(this.record.getCreationDate().replace(/:\d\d$/, ""));

	$tbody.append("<tr><td class='entry-edit-link-cell'></td><td class='entry-fields'/></tr>");

	this.renderFields = function() {
		var content = "<table><tbody>" +
			"<tr><td><h2>" + this.record.getDetail(Blog.titleDetailType) + "</h2></td></tr>";

		if (Blog.group) {
			content += "<tr class='entry-wg-tags-row'><td>Workgroup tags: <span class='entry-wg-tags-span'/></td></tr>";
		}
		content += "<tr class='entry-tags-row'><td>"+ (Blog.group ? "Personal tags" : "Tags") + ": <span class='entry-tags-span'/></td></tr>";

		$(".entry-fields", $tbody).empty().append(content);

		var $span = $(".entry-tags-span", this.$table);
		var tags = [];
		if (Blog.canEdit()) {
			if (this.record.isPersonalised()) {
				tags = this.record.getTags();
			}
		} else if (Blog.user) {
			tags = Blog.tags[this.record.getID()];
		}

		if (tags  &&  tags.length) {
			for (var i = 0; i < tags.length; ++i) {
				if (i > 0) {
					$span.append(", ");
				}
				$span.append(Blog.createTagLink(tags[i]));
			}
		} else {
			if (Blog.canEdit()) {
				$span.append("<span class='no-tags'>NO TAGS</span>");
				$span.append(" ");
				$("<a href='#'>please add</a>")
					.click(function() {
						that.edit();
						return false;
					})
					.appendTo($span);
			} else {
				$(".entry-tags-row", this.$table).remove();
			}
		}

		$span = $(".entry-wg-tags-span", this.$table);
		tags = this.record.getKeywords();
		if (tags  &&  tags.length) {
			for (var i = 0; i < tags.length; ++i) {
				if (tags[i].getWorkgroup() === Blog.group) {
					if (i > 0) {
						$span.append(", ");
					}
					$span.append(Blog.createWGTagLink(tags[i]));
				}
			}
		} else {
			if (Blog.canEdit()) {
				$span.append("<span class='no-tags'>NO TAGS</span>");
				$span.append(" ");
				$("<a href='#'>please add</a>")
					.click(function() {
						that.edit();
						return false;
					})
					.appendTo($span);
			} else {
				$(".entry-wg-tags-row", this.$table).remove();
			}
		}

	};
	this.renderFields();

	$tbody.append("<tr><td></td><td><div class='entry-content'/></td></tr>");

	$tbody.append("<tr class='entry-expand-row' style='display: none;'><td></td><td><div class='entry-expand'/></td></tr>");

	var expand = $("<a href='#' class='entry-expand-link'>More ...</a>")
		.click(function () {
			$(".entry-content, .woot-editor, .entry-expand", that.$table).toggleClass("abbreviated");
			return false;
		});

	var contract = $("<a href='#' class='entry-contract-link'>Less ...</a>")
		.click(function () {
			$(".entry-content, .woot-editor, .entry-expand", that.$table).toggleClass("abbreviated");
			return false;
		});

	$(".entry-expand", this.$table).append(expand, contract);

	$tbody.append("<tr class='entry-show-comments-row'><td class='entry-show-comments-cell'></td><td class='entry-comments-summary-cell'></td></tr>");
	$tbody.append("<tr class='entry-hide-comments-row' style='display: none;'><td class='entry-hide-comments-cell'></td><td class='entry-comments-heading-cell'></td></tr>");
	$tbody.append("<tr class='entry-comments-content-row' style='display: none;'><td class='entry-add-comment-link-cell'></td><td><div class='entry-comments'/></td></tr>");


	HAPI.WOOT.loadWoot("record:"+this.record.getID(), {
		onload: function(_,woot) { that.wootLoaded(woot); }
	});
	this.commentManager = new CommentManager($(".entry-comments", $tbody)[0], this.record);
	this.commentManager.printAllComments();

/*
	// control for toggling flat / nested comments
	var $div = $("<div><b>Comments</b></div>").prependTo($(".entry-comments", $tbody));

	var $radio;
	var r = Math.round(Math.random() * 1000000);

	$radio = $("<input type='radio' id='comments-flat-input-"+r+"' name='comments-radio-"+r+"'/>")
				.change(function() { that.setCommentsFlat(this.checked); });
	$("<label for='comments-flat-input-"+r+"' class='comments-flat-label'>flat</label>").append($radio).appendTo($div);

	$radio = $("<input type='radio' id='comments-nested-input-"+r+"' name='comments-radio-"+r+"' checked='true'/>")
				.change(function() { that.setCommentsFlat(!this.checked); });
	$("<label for='comments-nested-input-"+r+"' class='comments-nested-label'>nested</label>").append($radio).appendTo($div);
*/


	if (Blog.canEdit()) {
		$("<a href='#' title='edit'/>").addClass("entry-edit-link").append("<img src='../../common/images/edit-pencil.gif'/>")
			.click(function() { that.edit(); return false; }).appendTo($(".entry-edit-link-cell", $tbody));
	}

	$("<a href='#' title='add comment'/>").addClass("entry-add-comment-link").append("<img src='../../common/images/duplicate.gif'/>")
		.click(function() {
			if (Blog.user  ||  (Blog.group  &&  HCurrentUser.isInWorkgroup(Blog.group))) {
				that.commentManager.addComment();
			} else {
				alert("Comments only available to workgroup members.\n" +
					  "For an open comment blog use a personal blog.");
			}
			return false;
		}).appendTo($(".entry-add-comment-link-cell", $tbody));


	var showComments = function() {
		$(".entry-show-comments-row", $tbody).hide();
		$(".entry-hide-comments-row", $tbody).show();
		$(".entry-comments-content-row", $tbody).show();
		return false;
	};

	var hideComments = function() {
		$(".entry-comments-content-row", $tbody).hide();
		$(".entry-hide-comments-row", $tbody).hide();
		$(".entry-show-comments-row", $tbody).show();
		return false;
	};

	$("<a href='#' title='show comments'/>")
		.addClass("entry-show-comments-link")
		.append("<img src='../../common/images/tright.gif'/>")
		.click(showComments)
		.appendTo($(".entry-show-comments-cell", $tbody));

	$("<a href='#' title='show comments'/>")
		.addClass("entry-comments-summary-link")
		.text("Comments [" + this.record.getComments().length + "]")
		.click(showComments)
		.appendTo($(".entry-comments-summary-cell", $tbody));

	$("<a href='#' title='hide comments'/>")
		.addClass("entry-hide-comments-link")
		.append("<img src='../../common/images/tdown.gif'/>")
		.click(hideComments)
		.appendTo($(".entry-hide-comments-cell", $tbody));

	$("<a href='#' title='hide comments'/>")
		.text("Comments")
		.click(hideComments)
		.appendTo($(".entry-comments-heading-cell", $tbody));

	if (isNew) {
		this.$div.prependTo(parentElement);
	} else {
		this.$div.appendTo(parentElement);
	}


	this.renderAdditionalData = function () {
		$(".entry-left", this.$outerTbody).empty();
		var thumb = this.record.getDetail(Blog.thumbnailDetailType);
		if (thumb) {
			$(".entry-left", this.$outerTbody).append("<a href='" + thumb.getURL() + "' target='_blank'><img src='" + thumb.getThumbnailURL() + "&w=170&h=170'/></a>");
		}
		else {
			$("<a href='#' title='Click to add a thumbnail image'><img src='no_image.png'/></a>")
				.click(function () {
					that.edit();
					return false;
				})
				.appendTo($(".entry-left", this.$outerTbody));
		}

		var $mapDiv = $("<div class='map-section'>").appendTo($(".entry-left", this.$outerTbody));
		var $content = null;
		var mapURL = Blog.getStaticMapURL(this.record);
		if (Blog.canEdit()) {
			var $a = $("<a title='Click to edit' href='../../records/spatial/gigitiser-blog/edit-geos.html?cb=reload&id=" + this.record.getID() + "'>")
				.click(function () {
					window.open(this.href, "", "status=0,width=600,height=500");
					return false;
				});
			if (mapURL) {
				$a.append("<img class='map' src='" + mapURL + "'>");
				$content = $a;
			} else {
				$a.append("add location").attr("title", "Click to add a location");
				$mapDiv.css("text-align", "right").append($a);
			}
		} else if (mapURL) {
			$content = $("<img class='map' src='" + mapURL + "'>");
		}

		if ($content) {
			$mapDiv.append("<p class='show-map'><a href='#'><img src='../../common/images/tright.gif'></a> <a href='#'>Show map</a></p>");
			$mapDiv.append("<p class='hide-map' style='display: none;'><a href='#'><img src='../../common/images/tright.gif'></a> <a href='#'>Hide map</a></p>");
			$mapDiv.append("<div class='map-content' style='display: none;'>");
			$(".map-content", $mapDiv).append($content);
			$(".show-map a", $mapDiv).click(function () {
				$(".show-map", $mapDiv).hide();
				$(".hide-map, .map-content", $mapDiv).show();
				return false;
			});
			$(".hide-map a", $mapDiv).click(function () {
				$(".hide-map, .map-content", $mapDiv).hide();
				$(".show-map", $mapDiv).show();
				return false;
			});
		}

		$(".entry-left", this.$outerTbody).append("<p class='loading'>loading ...</p>");

		// related records
		HeuristScholarDB.loadRecords(
			new HSearch("relatedto:" + that.record.getID()),
			new HLoader(
				function (s,r,c) {
					that.relatedLoaded(r);
				},
				function (s, e) {
					alert("load failed: " + e);
				}
			)
		);
	};

	this.renderAdditionalData();

	this.relatedLoaded = function (records) {
		var l, i, type, $div, $p, $a, ids, link, relatedByType = {};
		l = records.length;

		for (i = 0; i < l; ++i) {
			type = records[i].getRecordType().getID();
			if (! relatedByType[type]) {
				relatedByType[type] = [];
			}
			relatedByType[type].push(records[i]);
		}

		$(".entry-left p.loading", this.$outerTbody).remove();

		$div = $("<div>").appendTo($(".entry-left", this.$outerTbody));
		$div.append("<p class='related-header'><img src='related.png' title='Related records in the database'/></p>");

		var makeRelatedLink = function(type, records) {
			var title, label, ids, l, i, link;
			label = type.getName();
			if (label.length > 15) {
				label = label.slice(0, 13) + " ...";
			}
			label += " [" + records.length + "]";

			link = (Blog.user ? "?u=" + Blog.user.getID() : "?g=" + Blog.group.getID()) +
				"&t=" + type.getID() +
				"&q=relatedto:" + that.record.getID();
			title = "Click to see these related " + type.getName() + " records in blog layout";
			return $("<a href='" + link + "' title='" + title + "'>" + label + "</a>");
		};

		// current type
		$p = $("<p class='related-link'/>").appendTo($div);
		$a = $("<a href='#'>add</a>")
			.click((function (type) {
				if (type == Blog.blogEntryRecordType.getID()) {
					return function () {
						Blog.newEntry(that.record);
						return false;
					};
				} else {
					return function () {
						window.open("../../records/addrec/add.php?addref=1&bib_reftype=" + type +
							"&related=" + that.record.getID(), "_blank");
						return false;
					};
				}
			})(Blog.type.getID()))
			.attr("title",
				"Click to add a new " + Blog.type.getName() +
				" related to this " + that.record.getRecordType().getName())
			.appendTo($p);
		if (relatedByType[Blog.type.getID()]) {
			$p.append("&nbsp&nbsp&nbsp&nbsp");
			makeRelatedLink(Blog.type, relatedByType[Blog.type.getID()]).appendTo($p);
			delete relatedByType[Blog.type.getID()];
		} else {
			$a.append("&nbsp&nbsp&nbsp&nbsp");
			var label = Blog.type.getName();
			if (label.length > 15) {
				label = label.slice(0, 13) + " ...";
			}
			$a.append(label);
		}

		for (type in relatedByType) {
			$p = $("<p class='related-link'/>").appendTo($div);

			$("<a href='#'>add</a>")
				.click((function(type) {
					return function () {
						window.open("../../records/addrec/add.php?addref=1&bib_reftype=" + type +
							"&related=" + that.record.getID(), "_blank");
						return false;
					};
				})(type))
				.attr("title",
					"Click to add a new " + HRecordTypeManager.getRecordTypeById(type).getName() +
					" related to this " + that.record.getRecordType().getName())
				.appendTo($p);

			$p.append("&nbsp&nbsp&nbsp&nbsp");

			makeRelatedLink(
				HRecordTypeManager.getRecordTypeById(type),
				relatedByType[type]
			).appendTo($p);
		}

		$p = $("<p class='related-link'/>").appendTo($div);
			$("<a href='#'>add</a>")
				.click(function () {
					window.open("add-record.html?id=" + that.record.getID() +
						(Blog.group ? "&g=" + Blog.group.getID() : ""), "_blank");
					return false;
				})
				.attr("title", "Click to add a new record of any type related to this " +
					that.record.getRecordType().getName())
				.append("&nbsp&nbsp&nbsp&nbsp")
				.append("other type")
				.appendTo($p);

		$p = $("<p class='related-link'/>").appendTo($div);
			$("<a href='#'/>")
				.click(function () {
					window.open("../../records/editrec/edit.html?bib_id=" + that.record.getID() +
						"#relationships", "_blank");
					return false;
				})
				.attr("title", "Click to add new or edit existing relationships for this " +
					that.record.getRecordType().getName())
				.append("edit")
				.append("&nbsp&nbsp&nbsp&nbsp")
				.append("<img src='../../common/images/follow_links_16x16.gif'/>")
				.append(" relationships")
				.appendTo($p);
	};

	this.wootLoaded = function(woot) {
		this.woot = woot;
		var text = '';
		for (var i = 0; i < this.woot.chunks.length; ++i) {
			text += this.woot.chunks[i].getText();
		}

		while (text.match(/<p>&#160;<\/p>$/)) {
			text = text.replace(/<p>&#160;<\/p>$/, "");
		}

		if (text.length > 1000) {
			$(".entry-expand-row", this.$div).show();
			$(".entry-content, .woot-editor, .entry-expand", this.$div).addClass("abbreviated");
		}

		if (Blog.canEdit()) {
			this.wootEditor = new HAPI.WOOT.GUI.WootEditor({ woot: this.woot, element: $(".entry-content", this.$div)[0] });

		if (text.length > 1000) {
			$(".woot-editor", this.$div).addClass("abbreviated");
		}

			// dodgy hack time - replace the onsubmit and oncancel handlers that the WootEditor puts on the editor form
			this.wootEditor.form.onsubmit = function() {
				setTimeout(function() {
					that.saveButtonClick();
				}, 0);
				return false;
			};

			this.wootEditor.form.oncancel = function() {
				setTimeout(function() {
					that.wootEditor.unlockedChunk.lock();
					$(".save-cancel-button-row", that.$table).remove();
					$(".entry-thumb-input-row", that.$table).remove();
					$(".entry-geo-input-row", that.$table).remove();
					that.renderFields();
				}, 0);
				return false;
			};

			// there's a distinction here between whether the woot is new (has not been saved before)
			// or the blog entry itself is new.  You could have an old blog entry with a new woot
			// (if the entry was created earlier but the woot for that entry was never saved)
			if (this.woot.id === "new") {
				if (Blog.user) {
					this.woot.setPermissions([ HAPI.WOOT.WORLD_READONLY_PROTECTION, HAPI.WOOT.OWNER_PROTECTION ]);
				} else {
					this.woot.setPermissions([ HAPI.WOOT.WORLD_READONLY_PROTECTION, new HAPI.WOOT.Permission({ "groupId": Blog.group.getID(), "type": "RW" }) ]);
				}
			}
			if (isNew) {
				this.edit();
			}
		} else {
			$(".entry-content", this.$div).append(text);
		}

		if (Blog.canEdit()  ||  HCurrentUser.isAdministrator()) {
			$("<a href='../../records/editrec/edit.html?bib_id=" + this.record.getID() + "#annotation' target='_blank' title='edit record in Heurist'>edit full record</a>")
				.addClass("heurist-edit-link")
				.appendTo($(".entry-date", this.$table))
				.click(function() {
					if ($(".save-cancel-button-row", that.$table).length) {
						that.saveButtonClick();
					}
				});
		}

		if (! this.record.getNonWorkgroupVisible()) {
			$("<span>visible to workgroup only</span>")
				.addClass("non-wg-visible-warning")
				.appendTo($(".entry-date", this.$table))
		}

		if (this.woot.chunks[0]) {
			var is_private = true;
			var perms = this.woot.chunks[0].permissions;
			var permstrings = [];
			for (var i = 0; i < perms.length; ++i) {
				var who, what, perm = perms[i];
				if (perm.groupName) {
					who = perm.groupName;
					is_private = false;
				} else if (perm.groupId === -1) {
					who = "Everyone";
					is_private = false;
				} else if (perm.userId) {
					if (perm.userId === -1) {
						who = this.woot.chunks[0].getOwnerRealName();
					} else {
						var user = HUserManager.getUserById(perm.userId);
						who = user ? user.getRealName() : "Unknown user";
					}
				}
				what = (perm.type === "RO") ? "read" : "read / write";
				permstrings.push(what + " : " + who);
			}
			$("<span class='entry-permissions'/>")
				.text(" [ " + permstrings.join(", ") + " ]")
				.appendTo($(".entry-date", this.$table));
			if (is_private) {
				this.$div.addClass("private");
			}

		}

		if (! Blog.user  ||  this.record.getCreator() !== Blog.user) {
			// "foreign" entry (let's use the same style for all entries in workgroup blogs)
			var user = this.record.getCreator();
			this.$div.addClass("foreign");
			$("<span class='entry-owner'/>")
				.text("by " + (user ? user.getRealName() : "Unknown user"))
				.appendTo($(".entry-date", this.$table));
		}
	};

	this.edit = function() {
		if ($(".save-cancel-button-row", this.$table).length) return;

		// woot
		if (this.wootEditor.chunks[0]) {
			this.wootEditor.chunks[0].unlock();
		} else {
			this.wootEditor.appendNewChunk(null);
		}


		var content = "<table><tbody>" +
			"<tr>" +
				"<td>Title: </td>" +
				"<td><input class='entry-title-input' value='" +
					this.record.getDetail(Blog.titleDetailType) + "'/></td>" +
			"</tr>";

		if (Blog.group) {
			content +=
				"<tr>" +
					"<td>Workgroup tags: </td>" +
					"<td><input class='entry-edit-wg-tags-input'/></td>" +
				"</tr>";
		}
		content +=
			"<tr>" +
				"<td>" + (Blog.group ? "Personal tags" : "Tags") + ": </td>" +
				"<td><input class='entry-edit-tags-input'></td>" +
			"</tr>";

		$(".entry-fields", this.$table).empty().append(content);

		if (Blog.group) {
			var $taginput = $(".entry-edit-wg-tags-input", this.table);
			var $select = $("<select><option value=''>Select a tag...</option></select>");
		    var keywords = HKeywordManager.getWorkgroupKeywords(Blog.group);
			var l = keywords.length;
			for (var i = 0; i < l; ++i) {
				(function (kwd) {
					$("<option>" + kwd + "</option>")
					.click(function () {
						var re = new RegExp('(^|,)' + kwd + '(,|$)');
						if (! re.test($taginput.val())) {
							if ($taginput.val().length > 0) {
								$taginput.val($taginput.val() + ",");
							}
							$taginput.val($taginput.val() + kwd);
						}
						$select.val("");
					})
					.appendTo($select);
				})(keywords[i].getName());
			}
			$(".entry-edit-wg-tags-input", this.table).after("&nbsp;&nbsp;add ", $select);
			if (HCurrentUser.isAdministrator()) {
				$select.after("<a target='_blank' href='../../admin/workgroups/workgroup_keyword_manager.php'>admin</a>");
			}
		}

		var tags = [];
		var $input = null;
		if (Blog.group) {
			var keywords = this.record.getKeywords();
			for (var i = 0; i < keywords.length; ++i) {
				tags.push(keywords[i].getName());
			}
			$input = $(".entry-edit-wg-tags-input", this.$table);
			$input.val(tags);
			new top.HEURIST.autocomplete.AutoComplete(
				$input[0],
				top.HEURIST.getKeywordAutofillFn(Blog.group)
			);
		}

		$input = $(".entry-edit-tags-input", this.$table);
		if (this.record.isPersonalised()) {
			$input.val(this.record.getTags())
		};
		new top.HEURIST.autocomplete.AutoComplete(
			$input[0],
			top.HEURIST.tagAutofill, { nonVocabularyCallback: top.HEURIST.showConfirmNewTag }
		);

		// thumbnail
		var $td = $("<td>Image:</td><td><input type='file' class='entry-thumb-input'/></td>");
		$("<tr class='entry-thumb-input-row'/>")
			.append($td)
			.appendTo($(".entry-fields tbody", this.$table));

		// geos
		var geos = record.getDetails(Blog.geoDetailType);
		var l = geos.length;
		var desc = "";
		for (var i = 0; i < l; ++i) {
			desc += (desc.length > 0 ? ", " : "") + geos[i].toString();
		}
		if (desc.length > 0) {
			$td = $("<td><img src='../../common/images/geo.gif'> " + desc + " <a>edit</td>");
		} else {
			$td = $("<td><a>add location</a></td>");
		}
		$("a", $td).attr("href", "../../spatial/gigitiser-blog/edit-geos.html?id=" + this.record.getID())
			.click(function () {
				window.open(this.href, "", "status=0,width=600,height=500");
				return false;
			});
		$("<tr class='entry-geo-input-row'/>")
			.append("<td>Location:</td>")
			.append($td)
			.appendTo($(".entry-fields tbody", this.$table));

		// buttons
		var $saveButton = $("<input type='button'/>").val("save").click(function() { that.saveButtonClick(); });
		var $cancelButton = $("<input type='button'/>").val("cancel").click(function() { that.cancelButtonClick(); });

		$td = $("<td/>").append($saveButton).append($cancelButton);
		var $tr = $("<tr/>").addClass("save-cancel-button-row").append("<td/>").append($td);
		$(".entry-show-comments-row", this.$table).before($tr);	// inserts our tr before the last tr in the table
	};

	this.saveButtonClick = function() {

		// title
		if ($(".entry-title-input", this.$table).val()) {
			this.record.setDetails(Blog.titleDetailType, [$(".entry-title-input", this.$table).val()]);
		} else if ($(".entry-title", this.$table).html()) {
			this.record.setDetails(Blog.titleDetailType, [$(".entry-title", this.$table).html()]);
		} else {
			alert("Enter a title for this blog entry");
			return;
		}

		// remove buttons
		$(".save-cancel-button-row", this.$table).remove();

		// tags
		var tags = $(".entry-edit-tags-input", this.$table).val();
		if (tags  &&  tags.length) {
			tags = tags.split(",");
		}

		var currentTags = this.record.isPersonalised() ? this.record.getTags() : [];
		for (var i = 0; i < currentTags.length; ++i) {
			this.record.removeTag(currentTags[i]);
		}
		if (tags  &&  tags.length) {
			if (! this.record.isPersonalised()) {
				this.record.addToPersonalised();
			}
			for (var i = 0; i < tags.length; ++i) {
				if (tags[i]){
					this.record.addTag(tags[i]);
				}
			}
		}
		if (Blog.group) {
			var wgtags = $(".entry-edit-wg-tags-input", this.$table).val();
			if (wgtags  &&  wgtags.length) {
				wgtags = wgtags.split(",");
			}

			var keywords = this.record.getKeywords();
			for (var i = 0; i < keywords.length; ++i) {
				if (keywords[i].getWorkgroup() === Blog.group) {
					this.record.removeKeyword(keywords[i]);
				}
			}

			if (wgtags  &&  wgtags.length) {
				keywords = HKeywordManager.getWorkgroupKeywords(Blog.group);
				for (var i = 0; i < wgtags.length; ++i) {
					for (var j = 0; j < keywords.length; ++j) {
						if (wgtags[i]){
							if (keywords[j].getName() === wgtags[i]) {
								this.record.addKeyword(keywords[j]);
							}
						}
					}
				}
			}
		}

		var saveFn = function () {
			HeuristScholarDB.saveRecord(that.record, new HSaver(
				function(r) {
					that.renderFields();
					that.renderAdditionalData();
					Blog.displayTagList();
					Blog.displayArchives();
				},
				function(r,e) {
					alert("record save failed: " + e);
				}
			));
		};

		var $fileInput = $(".entry-thumb-input-row input", this.$table);
		if ($fileInput.val()) {
			HeuristScholarDB.saveFile($fileInput[0], new HSaver(
				function(i,f) {
					that.record.setDetails(Blog.thumbnailDetailType, [f]);
					saveFn();
				},
				function(r,e) {
	                alert("file save failed: " + e);
	            }
			));
		} else {
			saveFn();
		}

		// save woot
		this.wootEditor.save();
	};

	this.cancelButtonClick = function() {
		this.wootEditor.form.oncancel();
	};


	this.setCommentsFlat = function(flat) {
		if (flat) {
			$(".entry-comments", this.$table).addClass("flat");
		} else {
			$(".entry-comments", this.$table).removeClass("flat");
		}
	};
},





loadTags: function() {
	HAPI.XHR.sendRequest("../../viewers/blog/get-tags.php?u=" + Blog.user.getID(),  function(response) {	//FIXME: we should bring this into the HAPI php commands
		Blog.tags = response;
		Blog.displayTagList();
		Blog.displayBlogEntries();
	});
},

displayTagList: function() {
	var tagCounts = {};
	var tags;
	var tagsElem = document.getElementById("tags");
	$(tagsElem).empty();

	if (Blog.query) {
		return;
	}

	if (Blog.user) {
		if (Blog.canEdit()) {
			for (var i = 0; i < Blog.records.length; ++i) {
				tags = Blog.records[i].getTags();
				for (var j = 0; j < tags.length; ++j) {
					tagCounts[tags[j]] = 1 + (tagCounts[tags[j]] ? tagCounts[tags[j]] : 0);
				}
			}
		} else {
			for (var i in Blog.tags) {
				tags = Blog.tags[i];
				for (var j = 0; j < tags.length; ++j) {
					tagCounts[tags[j]] = 1 + (tagCounts[tags[j]] ? tagCounts[tags[j]] : 0);
				}
			}
		}
	} else {
		for (var i = 0; i < Blog.records.length; ++i) {
			tags = Blog.records[i].getKeywords();
			for (var j = 0; j < tags.length; ++j) {
				if (tags[j].getWorkgroup() === Blog.group) {	// only show keywords relevant to this workgroup
					tagCounts[tags[j].getName()] = 1 + (tagCounts[tags[j].getName()] || 0);
				}
			}
		}
	}

	// sort the tags alphabetically
	tags = [];
	for (var tag in tagCounts) tags.push(tag);
	tags.sort(function(a,b) {
		var x = a.toLowerCase();
		var y = b.toLowerCase();
		return (x == y) ? 0 : ((x > y) ? 1 : -1);
	});

	var div;
	for (var i = 0; i < tags.length; ++i) {
		if (i === 0) {
			tagsElem.appendChild(document.createElement("h4")).appendChild(document.createTextNode("Tags"));
		}
		div = document.createElement("div");
		div.className = "tag";
		if (Blog.user) {
			div.appendChild(Blog.createTagLink(tags[i], tagCounts[tags[i]]));
		} else {
			div.appendChild(Blog.createWGTagLink(tags[i], tagCounts[tags[i]]));
		}
		tagsElem.appendChild(div);
	}
},

createTagLink: function(tag, count) {
	if (tag.getName) tag = tag.getName();
	return $("<a href='#'/>").addClass("tag-link").text(tag + (count ? (" [" + count + "]") : "")).click(function() {
		YAHOO.util.History.navigate("f", "//"+tag);
		return false;
	})[0];
},

createWGTagLink: function(tag, count) {
	if (tag.getName) tag = tag.getName();
	return $("<a href='#'/>").addClass("tag-link").text(tag + (count ? (" [" + count + "]") : "")).click(function() {
		YAHOO.util.History.navigate("f", "/"+tag+"/");
		return false;
	})[0];
},

/*
createRemoveTagLink: function(blogEntry, tag) {
	var a = document.createElement("a");
	a.className = "entry-remove-tag-link";
	a.title = "remove tag";
	a.href = "#";
	a.onclick = function() { blogEntry.removeTag(tag); return false; };
	a.appendChild(document.createElement("img")).src = "../../common/images/cross.gif";
	return a;
},
*/

displayArchives: function() {
	$(".archive-month").remove();
	if (Blog.query) {
		$("#archives").hide();
		return;
	}
	$("#archives").show();
	Blog.archivedEntries = {};
	for (var i = 0; i < Blog.records.length; ++i) {
		Blog.addArchivedBlogEntry(Blog.records[i]);
	}
},

addArchivedBlogEntry: function(record) {
	var archiveElem = document.getElementById("right");
	var d = new Date(record.getCreationDate().replace(/-/g, "/"));
	var y = d.getFullYear();
	var m = d.getMonth();
	if (! Blog.archivedEntries[y]) {
		Blog.archivedEntries[y] = {};
	}
	if (Blog.archivedEntries[y][m]) {
		Blog.archivedEntries[y][m].increment();
	} else {
		Blog.archivedEntries[y][m] = new Blog.ArchiveMonth(y, m, archiveElem);
	}
	//Blog.archivedEntries[y][m].addArchivedBlogEntry(record);
},

ArchiveMonth: function(year, month, elem) {
	this.count = 1;
	this.div = document.createElement("div"); this.div.className = "archive-month";
	this.titleElem = document.createElement("div"); this.titleElem.className = "archive-month-title";
	var a = document.createElement("a");
	a.href="#";
	a.className = "month-link";
	a.setAttribute("y", year);
	a.setAttribute("m", month);
	a.onclick = function() {
		YAHOO.util.History.navigate("f", Blog.buildMonthString(year, month));
		return false;
	};
	a.appendChild(document.createTextNode(Blog.monthNames[month] + " " + year + " [" + this.count + "]"));
	this.titleElem.appendChild(a);
	this.entryElem = document.createElement("div"); this.entryElem.className = "archive-entries";

	var now = new Date();
	if (year === now.getFullYear()  &&  month === now.getMonth()) {
		this.div.id = "archive-month-current";
		//this.div.style.display = "none";
	}

	this.div.appendChild(this.titleElem);
	this.div.appendChild(this.entryElem);
	elem.appendChild(this.div);

	this.increment = function() {
		$(a).text(Blog.monthNames[month] + " " + year + " [" + (++this.count) + "]");
	}
},

showCurrentMonth: function() {
//	var elem = document.getElementById("archive-month-current");
//	if (elem) elem.style.display = "";
},

hideCurrentMonth: function() {
//	var elem = document.getElementById("archive-month-current");
//	if (elem) elem.style.display = "none";
},


addRelationship: function (r1, r2, onSave) {
	var rel = new HRelationship(r1, "IsRelatedTo", r2);
	HeuristScholarDB.saveRecord(rel, new HSaver(
		function(r) {
			if (onSave) onSave(r);
		},
		function(r,e) {
			alert("record save failed: " + e);
		}
	));
},

newEntry: function(related) {
	if (Blog.newEntryDiv) return;
	var mainElem = document.getElementById("main");
	var record = new HRecord();
	record.setRecordType(Blog.type);
	record.setDetails(Blog.titleDetailType, ["Enter title here"]);
	if (Blog.user) {
		record.addToPersonalised();
	} else {
		record.setWorkgroup(Blog.group);
		record.setNonWorkgroupVisible(false);
	}

	HeuristScholarDB.saveRecord(record, new HSaver(
		function(r) {
			Blog.records.unshift(r);
			var f = function () {
				new Blog.BlogEntry(r, mainElem, true);
			};
			if (related) {
				Blog.addRelationship(related, r, f);
			} else {
				f();
			}
		},
		function(r,e) {
			alert("record creation failed: " + e);
		}
	));
},


search: function(opts) {
	var matches = Blog.records;

	if (! opts  &&  ! Blog.query) {
		matches = matches.slice(0, 5);
	}

	for (var opt in opts) {
		var newMatches = [];
		if (opt === "wgtag") {
			var tags;
			for (var i = 0, l = matches.length; i < l; ++i) {
				tags = matches[i].getKeywords();
				for (var j = 0; tags && j < tags.length; ++j) {
					if (Blog.group  &&  tags[j].getWorkgroup() === Blog.group  &&  tags[j].getName() === opts.wgtag) {
						newMatches.push(matches[i]);
						break;
					}
				}
			}
			matches = newMatches;
		} else if (opt === "tag") {
			var tags;
			for (var i = 0, l = matches.length; i < l; ++i) {
				if (Blog.canEdit()) {
					if (matches[i].isPersonalised()) {
						tags = matches[i].getTags();
					} else {
						tags = [];
					}
				} else {
					tags = Blog.tags[matches[i].getID()];
				}
				for (var j = 0; tags && j < tags.length; ++j) {
					if (tags[j] === opts.tag) {
						newMatches.push(matches[i]);
						break;
					}
				}
			}
			matches = newMatches;
		} else if (opt === "record") {
			for (var i = 0, l = matches.length; i < l; ++i) {
				if (matches[i].getID() === opts.record) {
					newMatches.push(matches[i]);
					break;
				}
			}
			matches = newMatches;
		} else if (opt === "year") {
			for (var i = 0, l = matches.length; i < l; ++i) {
				d = new Date(matches[i].getCreationDate().replace(/-/g, "/"));
				if (d.getFullYear() === opts.year  &&
					(! opts.month  ||  d.getMonth() === opts.month)  &&
					(! opts.day  ||  d.getDate() === opts.day)) {
					newMatches.push(matches[i]);
				}
			}
			matches = newMatches;
		}
	}

	var mainElem = document.getElementById("main");
	while (mainElem.lastChild) {
		mainElem.removeChild(mainElem.lastChild);
	}

	this.entries = [];

	if (matches.length === 0) {
		var div = document.createElement("div");
		div.className = "empty-message";
		div.appendChild(document.createTextNode("There are no entries yet for this month - if no months or tags are shown on the right, try another record type"));
		mainElem.appendChild(div);
	}

	for (var i = 0; i < matches.length; ++i) {
		this.entries.push(new Blog.BlogEntry(matches[i], mainElem));
	}

	// hide the current month archive if we're displaying the current month
	// in the main panel, otherwise show it
	/*
	var now = new Date();
	if ((opts.year  &&  opts.year == now.getFullYear())  &&
		(! opts.month  ||  opts.month == now.getMonth()) &&
		(! opts.day)) {
		Blog.hideCurrentMonth();
	} else {
		Blog.showCurrentMonth();
	}
	*/

	// higlight tag / date links
	$(".tag-link, .month-link").removeClass("highlight");
	if (opts  &&  opts.tag) {
		$(".tag-link:contains(" + opts.tag + ")").addClass("highlight");
	}
	if (opts  &&  opts.wgtag) {
		$(".tag-link:contains(" + opts.wgtag + ")").addClass("highlight");
	}
	if (opts  &&  opts.year  &&  opts.month  &&  ! opts.day) {
		$(".month-link[y=" + opts.year + "][m=" + opts.month + "]").addClass("highlight");
	}
},

getStaticMapURL: function (record) {
	var geos, l, i,
		markers = [],
		path = null,
		pathStr,
		url = "http://maps.google.com/maps/api/staticmap";

	url += "?size=170x170";
	url += "&key=ABQIAAAAGZugEZOePOFa_Kc5QZ0UQRQUeYPJPN0iHdI_mpOIQDTyJGt-ARSOyMjfz0UjulQTRjpuNpjk72vQ3w";
	url += "&sensor=false";
	url += "&maptype=terrain";

	geos = record.getDetails(Blog.geoDetailType);
	l = geos.length;
	for (i = 0; i < l; ++i) {
		if (HAPI.isA(geos[i], "HPointValue")) {
			markers.push(geos[i].getY() + "," + geos[i].getX());
		} else {
			if (! path) {
				path = GOI.google.getLatLngs(geos[i]);
			}
		}
	}

	if (markers.length === 0  &&  ! path) {
		return null;
	}

	if (markers.length > 0) {
		url += "&markers=" + markers.join("|");
	}

	if (path) {
		pathStrs = [];
		l = path.length;
		for (i = 0; i < l; ++i) {
			pathStrs.push(path[i].lat() + "," + path[i].lng());
		}
		url += "&path=weight:1|color:0xff00007f|fillcolor:0xff000040|" + pathStrs.join("|");
	}

	if (markers.length < 2  &&  ! path) {
		url += "&zoom=2";
	}

	return url;
}

};
