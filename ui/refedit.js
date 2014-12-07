//
// This app will handle the listing, additions and deletions of events.  These are associated business.
//
function ciniki_sponsors_refedit() {
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
			'ciniki_sponsors_refedit', 'details',
			'mc', 'medium', 'sectioned', 'ciniki.sponsors.refedit.details');
		this.details.data = {};
		this.details.detail_id = 0;
		this.details.object = '';
		this.details.object_id = '';
		this.edit.sections = {
            'general':{'label':'Title', 'fields':{
                'title':{'label':'Title', 'hint':'Sponsors', 'type':'text'},
                }}, 
            '_content':{'label':'', 'fields':{
                'content':{'label':'', 'type':'textarea', 'hidelabel':'yes'},
                }}, 
			'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Sponsor',
				'addFn':'M.ciniki_sponsors_refedit
				'addFn':'M.startApp(\'ciniki.sponsors.refedit\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'object\':\'ciniki.events.event\',\'object_id\':M.ciniki_events_main.event.event_id,\'sponsor_id\':\'0\'});',
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sponsors_refedit.saveDetails();'},
				}},
			};
		this.edit.addClose('Cancel');

		//
		// The sponsor edit panel
		//
		this.edit = new M.panel('Sponsor',
			'ciniki_sponsors_refedit', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.sponsors.refedit.edit');
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
				'save':{'label':'Save', 'fn':'M.ciniki_sponsors_refedit.saveSponsor();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_refedit.removeSponsor();'},
				}},
			};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'title' ) {
				M.api.getJSONBgCb('ciniki.sponsors.sponsorSearch',
					{'business_id':M.curBusinessID, 'start_needle':value, 'limit':25}, function(rsp) {
						M.ciniki_sponsors_refedit.edit.liveSearchShow(s, i, M.gE(M.ciniki_sponsors_refedit.edit.panelUID + '_' + i), rsp['sponsors']);
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
				return 'M.ciniki_sponsors_refedit.showSponsorEdit(null,null,null,null,\'' + d.sponsor.id + '\');';
			}
		};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			if( i == 'ref_sequence' ) {
				return {'method':'ciniki.sponsors.sponsorRefHistory', 'args':{'business_id':M.curBusinessID, 
					'ref_id':this.ref_id, 'field':'sequence'}};
			} else if( i == 'ref_webflags' ) {
				return {'method':'ciniki.sponsors.sponsorRefHistory', 'args':{'business_id':M.curBusinessID, 
					'ref_id':this.ref_id, 'field':'webflags'}};
			} else {
				return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'business_id':M.curBusinessID, 
					'sponsor_id':this.sponsor_id, 'field':i}};
			}
		};
		this.edit.addDropImage = function(iid) {
			M.ciniki_sponsors_refedit.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_sponsors_refedit.saveSponsor();');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sponsors_refedit', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		if( args.ref_id != null ) {
			this.showSponsorEdit(cb, args.ref_id);
		} else {
			this.showSponsorEdit(cb, 0, args.object, args.object_id, args.sponsor_id);
		}
	}

	this.showSponsorEdit = function(cb, rid, obj, oid, sid) {
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
					var p = M.ciniki_sponsors_refedit.edit;
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
					var p = M.ciniki_sponsors_refedit.edit;
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
					M.ciniki_sponsors_refedit.edit.close();
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
					M.ciniki_sponsors_refedit.edit.close();
				});
		}
	};

	this.removeSponsor = function() {
		if( confirm("Are you sure you want to remove this sponsor?") ) {
			M.api.getJSONCb('ciniki.sponsors.sponsorRefDelete', 
				{'business_id':M.curBusinessID, 
				'ref_id':M.ciniki_sponsors_refedit.edit.ref_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sponsors_refedit.edit.close();
				});
		}
	}
};
