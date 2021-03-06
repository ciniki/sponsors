//
// This app will handle the listing, additions and deletions of events.  These are associated tenant.
//
function ciniki_sponsors_main() {
    this.webFlags = {'1':{'name':'Hidden'}};
    this.sizeOptions = {'10':'Tiny', '20':'Small', '30':'Medium', '40':'Large', '50':'X-Large'};
    //
    // Panels
    //
    this.init = function() {
        //
        // The levels panel, if they have sponsor levels enabled
        //
        this.levels = new M.panel('Sponsorship Levels',
            'ciniki_sponsors_main', 'levels',
            'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.levels');
        this.levels.sections = {
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':2,
                'hint':'Search sponsors', 'noData':'No sponsors found',
                'headerValues':['Sponsors', 'Level'],
                },
            'levels':{'label':'Sponsorship Levels', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':[''],
                'noData':'No sponsor levels defined',
                'addTxt':'Add Sponsorship Level',
                'addFn':'M.ciniki_sponsors_main.showLevelEdit(\'M.ciniki_sponsors_main.showLevels();\',0);',
                },
            };
        this.levels.liveSearchCb = function(s, i, value) {
            if( s == 'search' && value != '' ) {
                M.api.getJSONBgCb('ciniki.sponsors.sponsorSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                    function(rsp) { 
                        M.ciniki_sponsors_main.levels.liveSearchShow('search', null, M.gE(M.ciniki_sponsors_main.levels.panelUID + '_' + s), rsp.sponsors); 
                    });
                return true;
            }
        };
        this.levels.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'search' ) { 
                switch(j) {
                    case 0: return d.sponsor.title;
                    case 1: return (d.sponsor.level_name!=null?d.sponsor.level_name:'No sponsorship level');
                }
            }
            return '';
        };
        this.levels.liveSearchResultRowFn = function(s, f, i, j, d) { 
            return 'M.ciniki_sponsors_main.showSponsorEdit(\'M.ciniki_sponsors_main.showLevels();\',\'' + d.sponsor.id + '\');'; 
        };
        this.levels.sectionData = function(s) { return this.data[s]; }
        this.levels.noData = function(s) { return this.sections[s].noData; }
        this.levels.cellValue = function(s, i, j, d) {
            return d.level.name + '<span class="count">' + d.level.num_sponsors + '</span>';
            };
        this.levels.rowFn = function(s, i, d) {
            return 'M.ciniki_sponsors_main.showSponsors(\'M.ciniki_sponsors_main.showLevels();\',\'' + d.level.id + '\',\'' + escape(d.level.name) + '\');';
        };
        this.levels.addClose('Back');

        //
        // The level edit panel
        //
        this.ledit = new M.panel('Sponsorship Level',
            'ciniki_sponsors_main', 'ledit',
            'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.ledit');
        this.ledit.data = null;
        this.ledit.sponsor_id = 0;
        this.ledit.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'general':{'label':'', 'fields':{
                'name':{'label':'Name', 'hint':'Level name', 'type':'text'},
                'sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
                'size':{'label':'Size', 'type':'toggle', 'toggles':this.sizeOptions},
                }}, 
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_sponsors_main.saveLevel();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_main.removeLevel();'},
                }},
            };
        this.ledit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.ledit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'tnid':M.curTenantID, 
                'sponsor_id':this.sponsor_id, 'field':i}};
        }
        this.ledit.addClose('Cancel');

        
        //
        // The sponsors panel
        //
        this.sponsors = new M.panel('Sponsors',
            'ciniki_sponsors_main', 'sponsors',
            'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.sponsors');
        this.sponsors.level_id = 0;
        this.sponsors.sections = {
            'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
                'headerValues':null,
                'cellClasses':[''],
                'noData':'No sponsors',
                'addTxt':'Add Sponsor',
                'addFn':'M.ciniki_sponsors_main.showSponsorEdit(\'M.ciniki_sponsors_main.showSponsors();\',0,M.ciniki_sponsors_main.sponsors.level_id);',
                },
            };
        this.sponsors.sectionData = function(s) { return this.data[s]; }
        this.sponsors.noData = function(s) { return this.sections[s].noData; }
        this.sponsors.cellValue = function(s, i, j, d) {
            return d.sponsor.title;
            };
        this.sponsors.rowFn = function(s, i, d) {
            return 'M.ciniki_sponsors_main.showSponsorEdit(\'M.ciniki_sponsors_main.showSponsors();\',\'' + d.sponsor.id + '\');';
        };
        this.sponsors.addClose('Back');

        //
        // The sponsor edit panel
        //
        this.sedit = new M.panel('Sponsor',
            'ciniki_sponsors_main', 'sedit',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.sponsors.main.sedit');
        this.sedit.data = null;
        this.sedit.level_id = 0;
        this.sedit.sponsor_id = 0;
        this.sedit.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'general':{'label':'General', 'aside':'yes', 'fields':{
                'title':{'label':'Name', 'hint':'Sponsor name', 'type':'text'},
                'level_id':{'label':'Level', 'active':'no', 'type':'select', 'options':{}},
                'sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
                'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
                'url':{'label':'URL', 'hint':'Enter the http:// address for the sponsors website', 'type':'text'},
                }}, 
            '_excerpt':{'label':'Description', 'fields':{
                'excerpt':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
                }},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_sponsors_main.saveSponsor();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_main.removeSponsor();'},
                }},
            };
        this.sedit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.sedit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'tnid':M.curTenantID, 
                'sponsor_id':this.sponsor_id, 'field':i}};
        }
        this.sedit.addDropImage = function(iid) {
            M.ciniki_sponsors_main.sedit.setFieldValue('primary_image_id', iid, null, null);
            return true;
        };
        this.sedit.deleteImage = function(fid) {
            this.setFieldValue(fid, 0, null, null);
            return true;
        };
        this.sedit.addButton('save', 'Save', 'M.ciniki_sponsors_main.saveSponsor();');
        this.sedit.addClose('Cancel');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sponsors_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( (M.curTenant.modules['ciniki.sponsors'].flags&0x01) > 0 ) { 
            this.showLevels(cb);
        } else {
            this.showSponsors(cb, 0, 'Sponsors');
        }
    }

    //
    // Level functions
    //
    this.showLevels = function(cb) {
        M.api.getJSONCb('ciniki.sponsors.levelList', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sponsors_main.levels;
                p.data = {'levels':rsp.levels};
                p.refresh();
                p.show(cb);
            });
    };

    this.showLevelEdit = function(cb, lid) {
        this.ledit.reset();
        if( lid != null ) { this.ledit.level_id = lid; }
        if( this.ledit.level_id > 0 ) {
            M.api.getJSONCb('ciniki.sponsors.levelGet', {'tnid':M.curTenantID, 
                'level_id':this.ledit.level_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sponsors_main.ledit;
                    p.data = rsp.level;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.ledit.data = {'size':30};
            this.ledit.show(cb);
        }
    };

    this.saveLevel = function() {
        if( this.ledit.level_id > 0 ) {
            var c = this.ledit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.levelUpdate', 
                    {'tnid':M.curTenantID, 'level_id':M.ciniki_sponsors_main.ledit.level_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_sponsors_main.ledit.close();
                    });
            } else {
                this.ledit.close();
            }
        } else {
            var c = this.ledit.serializeForm('yes');
            M.api.postJSONCb('ciniki.sponsors.levelAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sponsors_main.ledit.close();
                });
        }
    };

    this.removeLevel = function() {
        M.confirm("Are you sure you want to remove this level?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.levelDelete', 
                {'tnid':M.curTenantID, 
                'level_id':M.ciniki_sponsors_main.ledit.level_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sponsors_main.ledit.close();
                });
        });
    }

    //
    // Sponsor functions
    //
    this.showSponsors = function(cb, lid, lname) {
        if( lid != null ) { this.sponsors.level_id = lid; }
        if( lname != null && lname != '' ) { this.sponsors.sections.sponsors.label = unescape(lname); }
        // Add edit level button to top right
        if( this.sponsors.level_id > 0 ) {
            this.sponsors.addButton('edit', 'Edit', 'M.ciniki_sponsors_main.showLevelEdit(\'M.ciniki_sponsors_main.showSponsors();\',\'' + this.sponsors.level_id + '\');');
        } else {
            if( this.sponsors.rightbuttons['edit'] != null ) {
                delete this.sponsors.rightbuttons['edit'];
            }
        }
        M.api.getJSONCb('ciniki.sponsors.sponsorList', 
            {'tnid':M.curTenantID, 'level_id':this.sponsors.level_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sponsors_main.sponsors;
                p.data = {'sponsors':rsp.sponsors};
                p.refresh();
                p.show(cb);
            });
    };

    this.showSponsorEdit = function(cb, sid, lid) {
        this.sedit.reset();
        if( sid != null ) { this.sedit.sponsor_id = sid; }
        if( this.sedit.sponsor_id > 0 ) {
            this.sedit.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.sponsors.sponsorGet', {'tnid':M.curTenantID, 
                'sponsor_id':this.sedit.sponsor_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sponsors_main.sedit;
                    p.data = rsp.sponsor;
                    if( (M.curTenant.modules['ciniki.sponsors'].flags&0x01) > 0 ) { 
                        p.sections.general.fields.level_id.options = {};
                        for(i in rsp.levels) {
                            p.sections.general.fields.level_id.options[rsp.levels[i].level.id] = rsp.levels[i].level.name;
                        }
                        p.sections.general.fields.level_id.active = 'yes';
                    } else {
                        p.sections.general.fields.level_id.active = 'no';
                    }
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.sedit.sections._buttons.buttons.delete.visible = 'no';
            this.sedit.data = {};
            if( lid != null && lid != 0 ) {
                this.sedit.data.level_id = lid;
            }
            if( (M.curTenant.modules['ciniki.sponsors'].flags&0x01) > 0 ) { 
                var lvls = this.levels.data.levels;
                for(i in lvls) {
                    this.sedit.sections.general.fields.level_id.options[lvls[i].level.id] = lvls[i].level.name;
                }
                this.sedit.sections.general.fields.level_id.active = 'yes';
            } else {
                this.sedit.sections.general.fields.level_id.active = 'no';
            }
            this.sedit.refresh();
            this.sedit.show(cb);
        }
    };

    this.saveSponsor = function() {
        if( this.sedit.sponsor_id > 0 ) {
            var c = this.sedit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.sponsorUpdate', 
                    {'tnid':M.curTenantID, 'sponsor_id':this.sedit.sponsor_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_sponsors_main.sedit.close();
                    });
            } else {
                this.sedit.close();
            }
        } else {
            var c = this.sedit.serializeForm('yes');
            M.api.postJSONCb('ciniki.sponsors.sponsorAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sponsors_main.sedit.close();
                });
        }
    };

    this.removeSponsor = function() {
        M.confirm("Are you sure you want to remove this sponsor?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.sponsorDelete', 
                {'tnid':M.curTenantID, 
                'sponsor_id':M.ciniki_sponsors_main.sedit.sponsor_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sponsors_main.sedit.close();
                });
        });
    }
};
