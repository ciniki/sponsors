//
// This app will handle the sponsors management for an object in ciniki.
//
function ciniki_sponsors_ref() {
	this.webFlags = {'1':{'name':'Hidden'}};
	this.sizeOptions = {'10':'Tiny', '20':'Small', '30':'Medium', '40':'Large', '50':'X-Large'};
	//
	// Panels
	//
	this.init = function() {
		//
		// The ref details panel
		//
		this.details = new M.panel('Sponsor',
			'ciniki_sponsors_ref', 'details',
			'mc', 'medium', 'sectioned', 'ciniki.sponsors.ref.details');
		this.details.data = {};
		this.details.detail_id = 0;
		this.details.ref_object = '';
		this.details.ref_object_id = '';
		this.details.sections = {
            'general':{'label':'', 'fields':{
                'title':{'label':'Title', 'hint':'Sponsors', 'type':'text'},
                'size':{'label':'Size', 'type':'toggle', 'toggles':this.sizeOptions},
                }}, 
            '_content':{'label':'', 'fields':{
                'content':{'label':'', 'type':'textarea', 'hidelabel':'yes'},
                }}, 
			'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Sponsor',
				'addFn':'M.ciniki_sponsors_ref.editSponsor(\'M.ciniki_sponsors_ref.updateSponsors();\',0,M.ciniki_sponsors_ref.details.ref_object,M.ciniki_sponsors_ref.details.ref_object_id,0);',
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sponsors_ref.saveDetails();'},
				}},
			};
		this.details.sectionData = function(s) { return this.data[s]; }
		this.details.fieldValue = function(s, i, d) { return this.data[i]; }
		this.details.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sponsors.sponsorRefDetailHistory', 'args':{'business_id':M.curBusinessID, 
				'detail_id':this.detail_id, 'field':i}};
		};
		this.details.cellValue = function(s, i, j, d) {
			if( s == 'sponsors' && j == 0 ) { 
				return '<span class="maintext">' + d.sponsor.title + '</span>';
			}
		};
		this.details.rowFn = function(s, i, d) {
			if( s == 'sponsors' ) {
				return 'M.ciniki_sponsors_ref.editSponsor(\'M.ciniki_sponsors_ref.updateSponsors();\',\'' + d.sponsor.ref_id + '\');';
			}
		};
		this.details.addButton('save', 'Save', 'M.ciniki_sponsors_ref.saveDetails();');
		this.details.addClose('Cancel');

		//
		// The sponsor edit panel
		//
		this.edit = new M.panel('Sponsor',
			'ciniki_sponsors_ref', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.sponsors.ref.edit');
		this.edit.data = {};
		this.edit.level_id = 0;
		this.edit.ref_id = 0;
		this.edit.object = '';
		this.edit.object_id = '';
		this.edit.sponsor_id = 0;
		this.edit.sections = {
			'_image':{'label':'', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
			}},
            'general':{'label':'General', 'fields':{
                'title':{'label':'Name', 'hint':'Sponsor name', 'type':'text', 'livesearch':'yes'},
                'ref_sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
				'ref_webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
                'url':{'label':'URL', 'hint':'Enter the http:// address for the sponsors website', 'type':'text'},
                }}, 
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sponsors_ref.saveSponsor();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_ref.removeSponsor();'},
				}},
			};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'title' ) {
				M.api.getJSONBgCb('ciniki.sponsors.sponsorSearch',
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) {
						M.ciniki_sponsors_ref.edit.liveSearchShow(s, i, M.gE(M.ciniki_sponsors_ref.edit.panelUID + '_' + i), rsp['sponsors']);
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'title' ) {
				return d.sponsor.title;
			}
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( f == 'title' ) {
				return 'M.ciniki_sponsors_ref.editSponsor(null,null,null,null,\'' + d.sponsor.id + '\');';
			}
		};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			if( i == 'ref_sequence' || i == 'ref_webflags' ) {
				return {'method':'ciniki.sponsors.sponsorRefHistory', 'args':{'business_id':M.curBusinessID, 
					'ref_id':this.ref_id, 'field':i}};
			} else {
				return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'business_id':M.curBusinessID, 
					'sponsor_id':this.sponsor_id, 'field':i}};
			}
		};
		this.edit.addDropImage = function(iid) {
			M.ciniki_sponsors_ref.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_sponsors_ref.saveSponsor();');
		this.edit.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_sponsors_ref', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		if( args.ref_id != null ) {
			this.editSponsor(cb, args.ref_id);
		} else if( args.object != null && args.object_id != null ) {
			this.editDetails(cb, 0, args.object, args.object_id);
		} else {
			this.editSponsor(cb, 0, args.object, args.object_id, args.sponsor_id);
		}
	}

	this.editDetails = function(cb, did, obj, oid) {
		if( did != null ) { this.details.reset(); this.details.detail_id = did; }
		if( obj != null ) { this.details.ref_object = obj; }
		if( oid != null ) { this.details.ref_object_id = oid; }
		if( this.details.detail_id > 0 ) {
			M.api.getJSONCb('ciniki.sponsors.refDetailGet', {'business_id':M.curBusinessID, 
				'detail_id':this.details.detail_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.details;
					p.data = rsp.detail;
					p.ref_object = rsp.detail.object;
					p.ref_object_id = rsp.detail.object_id;
					p.refresh();
					p.show(cb);
				});
		} else if( this.details.ref_object != null && this.details.ref_object_id != null ) {
			M.api.getJSONCb('ciniki.sponsors.refDetailGet', {'business_id':M.curBusinessID, 
				'object':this.details.ref_object, 'object_id':this.details.ref_object_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.details;
					p.data = rsp.detail;
					p.detail_id = rsp.detail.id;
					p.ref_object = rsp.detail.object;
					p.ref_object_id = rsp.detail.object_id;
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.updateSponsors = function() {
		if( this.details.detail_id > 0 ) {
			M.api.getJSONCb('ciniki.sponsors.refDetailGet', {'business_id':M.curBusinessID, 
				'detail_id':this.details.detail_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.details;
					p.data.sponsors = rsp.detail.sponsors;
					p.refreshSection('sponsors');
					p.show();
				});
		} else if( this.details.ref_object != null && this.details.ref_object_id != null ) {
			M.api.getJSONCb('ciniki.sponsors.refDetailGet', {'business_id':M.curBusinessID, 
				'object':this.details.ref_object, 'object_id':this.details.ref_object_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.details;
					p.data.sponsors = rsp.detail.sponsors;
					p.refreshSection('sponsors');
					p.show();
				});
		}
	};

	this.saveDetails = function() {
		if( this.details.detail_id > 0 ) {
			var c = this.details.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sponsors.refDetailUpdate', 
					{'business_id':M.curBusinessID, 'detail_id':this.details.detail_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_sponsors_ref.details.close();
					});
			} else {
				this.details.close();
			}
		} else {
			var c = this.details.serializeForm('yes');
			c += '&object=' + encodeURIComponent(this.details.ref_object);
			c += '&object_id=' + encodeURIComponent(this.details.ref_object_id);
			M.api.postJSONCb('ciniki.sponsors.refDetailAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_sponsors_ref.details.close();
				});
		}
	};

	this.editSponsor = function(cb, rid, obj, oid, sid) {
		if( rid != null ) { this.edit.reset(); this.edit.ref_id = rid; }
		if( obj != null ) { this.edit.ref_object = obj; }
		if( oid != null ) { this.edit.ref_object_id = oid; }
		if( sid != null ) { this.edit.sponsor_id = sid; }
		if( this.edit.ref_id > 0 && sid == null ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.sponsors.sponsorRefGet', {'business_id':M.curBusinessID, 
				'ref_id':this.edit.ref_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.edit;
					p.data = rsp.sponsor;
					p.ref_object = rsp.sponsor.ref_object;
					p.ref_object_id = rsp.sponsor.ref_object_id;
					p.sponsor_id = rsp.sponsor.sponsor_id;
					p.refresh();
					p.show(cb);
				});
		} else if( this.edit.sponsor_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			M.api.getJSONCb('ciniki.sponsors.sponsorGet', {'business_id':M.curBusinessID, 
				'sponsor_id':this.edit.sponsor_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sponsors_ref.edit;
					if( p.data == null ) { p.data = {}; }
					p.data.title = rsp.sponsor.title;
					p.data.url = rsp.sponsor.url;
					p.data.primary_image_id = rsp.sponsor.primary_image_id;
					p.data.sponsor_id = 0;
					p.refresh();
					p.show(cb);
				});
			
		} else {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveSponsor = function() {
		if( this.edit.ref_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( this.edit.data.sponsor_id == null || this.edit.data.sponsor_id != this.edit.sponsor_id ) {
				c += '&sponsor_id=' + encodeURIComponent(this.edit.sponsor_id);
			}
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sponsors.sponsorRefUpdate', 
					{'business_id':M.curBusinessID, 'ref_id':this.edit.ref_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_sponsors_ref.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			c += '&object=' + encodeURIComponent(this.edit.ref_object);
			c += '&object_id=' + encodeURIComponent(this.edit.ref_object_id);
			c += '&sponsor_id=' + encodeURIComponent(this.edit.sponsor_id);
			M.api.postJSONCb('ciniki.sponsors.sponsorRefAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_sponsors_ref.edit.close();
				});
		}
	};

	this.removeSponsor = function() {
		if( confirm("Are you sure you want to remove this sponsor?") ) {
			M.api.getJSONCb('ciniki.sponsors.sponsorRefDelete', 
				{'business_id':M.curBusinessID, 
				'ref_id':M.ciniki_sponsors_ref.edit.ref_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sponsors_ref.edit.close();
				});
		}
	}
};
