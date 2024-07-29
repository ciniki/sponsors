//
// This app will handle the listing, additions and deletions of events.  These are associated tenant.
//
function ciniki_sponsors_main() {
    this.webFlags = {'1':{'name':'Hidden'}};
    this.sizeOptions = {'10':'Tiny', '20':'Small', '30':'Medium', '40':'Large', '50':'X-Large'};

    //
    // The sponsors panel
    //
    this.sponsors = new M.panel('Sponsors',
        'ciniki_sponsors_main', 'sponsors',
        'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.sponsors');
    this.sponsors.level_id = 0;
    this.sponsors.category_id = 0;
    this.sponsors.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'categories', 'aside':'yes',
            'visible':function() { console.log(M.modFlagSet('ciniki.sponsors', 0x05)); return M.modFlagSet('ciniki.sponsors', 0x05); },
            'tabs':{
                'categories':{'label':'Categories', 'fn':'M.ciniki_sponsors_main.sponsors.switchTab("categories");'},
                'levels':{'label':'Levels', 'fn':'M.ciniki_sponsors_main.sponsors.switchTab("levels");'},
            }},
        'levels':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.modFlagOn('ciniki.sponsors', 0x01) && M.ciniki_sponsors_main.sponsors.sections._tabs.selected == 'levels' ? 'yes' : 'no'; },
            'editFn':function(s, i, d) {
                return 'M.ciniki_sponsors_main.level.open(\'M.ciniki_sponsors_main.sponsors.open();\',\'' + d.id + '\');';
            },
            'addTxt':'Add Level',
            'addFn':'M.ciniki_sponsors_main.level.open(\'M.ciniki_sponsors_main.sponsors.open();\',0);',
            },
        'categories':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.modFlagOn('ciniki.sponsors', 0x04) && M.ciniki_sponsors_main.sponsors.sections._tabs.selected == 'categories' ? 'yes' : 'no'; },
            'cellClasses':['multiline'],
            'editFn':function(s, i, d) {
                return 'M.ciniki_sponsors_main.category.open(\'M.ciniki_sponsors_main.sponsors.open();\',\'' + d.id + '\');';
            },
            'seqDrop':function(e,from,to) {
                M.api.getJSONCb('ciniki.sponsors.categoryUpdate', {'tnid':M.curTenantID, 
                    'category_id':M.ciniki_sponsors_main.sponsors.data.categories[from].id, 
                    'sequence':M.ciniki_sponsors_main.sponsors.data.categories[to].sequence, 
                    'categories':'yes',
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_sponsors_main.sponsors;
                        p.data.categories = rsp.categories;
                        p.refreshSection("categories");
                    });
                },
            'menu':{
                'add':{
                    'label':'Add Category',
                    'fn':'M.ciniki_sponsors_main.category.open(\'M.ciniki_sponsors_main.sponsors.open();\',0);',
                    },
                },
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'hint':'Search Sponsors', 
            'noData':'No sponsors found',
//            'headerValues':['Sponsors', 'Level'],
            },
        'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No sponsors',
            'menu':{
                'add':{
                    'label':'Add Sponsor',
                    'fn':'M.ciniki_sponsors_main.sponsor.open(\'M.ciniki_sponsors_main.sponsors.open();\',0,M.ciniki_sponsors_main.sponsors.level_id);',
                    },
                },
            },
        };
    this.sponsors.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.sponsors.sponsorSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_sponsors_main.sponsors.liveSearchShow('search', null, M.gE(M.ciniki_sponsors_main.sponsors.panelUID + '_' + s), rsp.sponsors); 
                });
            return true;
        }
    };
    this.sponsors.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch(j) {
                case 0: return d.title;
                case 1: return (d.level_name!=null?d.level_name:'No sponsorship level');
            }
        }
        return '';
    };
    this.sponsors.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_sponsors_main.sponsor.open(\'M.ciniki_sponsors_main.sponsors.open();\',\'' + d.id + '\');'; 
    };
    this.sponsors.sectionData = function(s) { return this.data[s]; }
    this.sponsors.noData = function(s) { return this.sections[s].noData; }
    this.sponsors.cellValue = function(s, i, j, d) {
        if( s == 'levels' ) {
            return M.textCount(d.name, d.num_sponsors);
        } else if( s == 'categories' ) {
            return M.multiline(M.textCount(d.name, d.num_sponsors), d.date_range);
        } else if( s == 'sponsors' ) {
            switch(j) {
                case 0: return M.multiline(d.title, d.summary);
                case 1: return d.sponsorship_amount_display;
                case 2: return d.inkind_value_display;
                case 3: return d.inkind_amount_display;
            }
        }
    }
    this.sponsors.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.open();
    }
    this.sponsors.footerValue = function(s, i, sc) {
        if( s == 'sponsors' && M.modFlagOn('ciniki.sponsors', 0x04) ) {
            switch(i) {
                case 0: return 'Totals';
                case 1: return this.data.totals.sponsorship_amount_display;
                case 2: return this.data.totals.inkind_value_display;
                case 3: return this.data.totals.inkind_amount_display;
            }
            return '';
        }
        return null;
    }
    this.sponsors.rowClass = function(s, i, d) {
        if( s == 'levels' && this.level_id == d.id ) {
            return 'highlight';
        }
        if( s == 'categories' && this.category_id == d.id ) {
            return 'highlight';
        }
        return '';
    }
    this.sponsors.rowFn = function(s, i, d) {
        if( s == 'levels' ) {
            return 'M.ciniki_sponsors_main.sponsors.openLevel("' + d.id + '");';
        }
        if( s == 'categories' ) {
            return 'M.ciniki_sponsors_main.sponsors.openCategory("' + d.id + '");';
        }
        if( s == 'sponsors' ) {
            return 'M.ciniki_sponsors_main.sponsor.open(\'M.ciniki_sponsors_main.sponsors.open();\',\'' + d.id + '\');';
        }
    }
    this.sponsors.openLevel = function(l) {
        this.level_id = l;
        this.open();
    }
    this.sponsors.openCategory = function(c) {
        this.category_id = c;
        this.open();
    }
    this.sponsors.downloadExcel = function() {
        M.api.openFile('ciniki.sponsors.sponsors', {'tnid':M.curTenantID, 'output':'excel', 'category_id':this.category_id});
        return false;
    }
    this.sponsors.open = function(cb, lid, lname) {
        if( lid != null ) { this.level_id = lid; }
//        if( lname != null && lname != '' ) { 
//            this.sections.sponsors.label = unescape(lname); 
//        }
        // Add edit level button to top right
//        if( this.level_id > 0 ) {
//            this.addButton('edit', 'Edit', 'M.ciniki_sponsors_main.level.open(\'M.ciniki_sponsors_main.sponsors.open();\',\'' + this.level_id + '\');');
//        } else {
//            if( this.rightbuttons['edit'] != null ) {
//                delete this.rightbuttons['edit'];
//            }
//        }
        var args = {'tnid':M.curTenantID};
        if( M.modFlagOn('ciniki.sponsors', 0x01) && this.sections._tabs.selected == 'levels' ) {
            args['level_id'] = this.level_id; 
        }
        this.delButton('download');
        if( M.modFlagOn('ciniki.sponsors', 0x04) && this.sections._tabs.selected == 'categories' ) {
            args['category_id'] = this.category_id; 
            if( this.category_id > 0 ) {
                this.addButton('download', 'Excel', 'M.ciniki_sponsors_main.sponsors.downloadExcel();');
            }
        }
        if( M.modFlagOn('ciniki.sponsors', 0x04) ) {
            M.api.getJSONCb('ciniki.sponsors.sponsors', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sponsors_main.sponsors;
                p.data = rsp;
                if( p.sections._tabs.selected == 'levels' ) {
                    p.sections.sponsors.num_cols = 1;
                } else {
                    p.sections.sponsors.num_cols = 4;
                }
                p.refresh();
                p.show(cb);
            });
        } else {
            M.api.getJSONCb('ciniki.sponsors.sponsorList', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sponsors_main.sponsors;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.sponsors.addClose('Back');

    //
    // The level edit panel
    //
    this.level = new M.panel('Sponsorship Level',
        'ciniki_sponsors_main', 'level',
        'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.level');
    this.level.data = null;
    this.level.sponsor_id = 0;
    this.level.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'hint':'Level name', 'type':'text'},
            'sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
            'size':{'label':'Size', 'type':'toggle', 'toggles':this.sizeOptions},
            }}, 
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sponsors_main.level.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_main.level.remove();'},
            }},
        };
    this.level.fieldValue = function(s, i, d) { return this.data[i]; }
    this.level.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'tnid':M.curTenantID, 
            'sponsor_id':this.sponsor_id, 'field':i}};
    }
    this.level.open = function(cb, lid) {
        this.reset();
        if( lid != null ) { this.level_id = lid; }
        if( this.level_id > 0 ) {
            M.api.getJSONCb('ciniki.sponsors.levelGet', {'tnid':M.curTenantID, 
                'level_id':this.level_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sponsors_main.level;
                    p.data = rsp.level;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.data = {'size':30};
            this.show(cb);
        }
    }
    this.level.save = function() {
        if( this.level_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.levelUpdate', 
                    {'tnid':M.curTenantID, 'level_id':M.ciniki_sponsors_main.level.level_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_sponsors_main.level.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sponsors.levelAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sponsors_main.level.close();
                });
        }
    }
    this.level.remove = function() {
        M.confirm("Are you sure you want to remove this level?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.levelDelete', 
                {'tnid':M.curTenantID, 
                'level_id':M.ciniki_sponsors_main.level.level_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sponsors_main.level.close();
                });
        });
    }
    this.level.addClose('Cancel');

    //
    // The category edit panel
    //
    this.category = new M.panel('Sponsorship Category',
        'ciniki_sponsors_main', 'category',
        'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.category');
    this.category.data = null;
    this.category.sponsor_id = 0;
    this.category.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'hint':'Level name', 'type':'text'},
            'sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
            'start_dt':{'label':'Start Date', 'type':'datetime'},
            'end_dt':{'label':'End Date', 'type':'datetime'},
            }}, 
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sponsors_main.category.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_sponsors_main.category.sponsor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_sponsors_main.category.remove();',
                },
            }},
        };
    this.category.fieldValue = function(s, i, d) { return this.data[i]; }
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'tnid':M.curTenantID, 
            'sponsor_id':this.sponsor_id, 'field':i}};
    }
    this.category.open = function(cb, lid) {
        this.reset();
        if( lid != null ) { this.category_id = lid; }
        M.api.getJSONCb('ciniki.sponsors.categoryGet', {'tnid':M.curTenantID, 
            'category_id':this.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sponsors_main.category;
                p.data = rsp.category;
                p.refresh();
                p.show(cb);
            });
    }
    this.category.save = function() {
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.categoryUpdate', 
                    {'tnid':M.curTenantID, 'category_id':M.ciniki_sponsors_main.category.category_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_sponsors_main.category.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sponsors.categoryAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sponsors_main.category.close();
                });
        }
    }
    this.category.remove = function() {
        M.confirm("Are you sure you want to remove this category?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.categoryDelete', 
                {'tnid':M.curTenantID, 
                'category_id':M.ciniki_sponsors_main.category.category_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sponsors_main.category.close();
                });
        });
    }
    this.category.addClose('Cancel');

    //
    // The sponsor edit panel
    //
    this.sponsor = new M.panel('Sponsor',
        'ciniki_sponsors_main', 'sponsor',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.sponsors.main.sponsor');
    this.sponsor.data = null;
    this.sponsor.level_id = 0;
    this.sponsor.sponsor_id = 0;
    this.sponsor.sections = {
        'general':{'label':'Sponsor', 'aside':'yes', 'fields':{
            'title':{'label':'Name', 'hint':'Sponsor name', 'type':'text', 'livesearch':'yes'},
            'level_id':{'label':'Level', 'active':'no', 'type':'select', 'options':{}},
            'sequence':{'label':'Sequence', 'hint':'1-255', 'type':'text', 'size':'small'},
            'webflags':{'label':'Website', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
            'url':{'label':'URL', 'hint':'Enter the http:// address for the sponsors website', 'type':'text'},
            }}, 
        '_categories':{'label':'Categories', 'aside':'yes', 
            'visible':function() {return M.modFlagSet('ciniki.sponsors', 0x04); },
            'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[]},
                'summary':{'label':'', 'hidelabel':'yes', 'hint':'short admin notes', 'type':'text'},
            }},
        'customer_details':{'label':'Linked Customer Business Account', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.sponsors', 0x04); },
            'cellClasses':['label', ''],
            'noData':'No linked customer account',
            'customer_id':0,
//            'addTxt':'Edit Customer Account',
//            'addFn':'M.ciniki_sponsors_main.sponsor.editCustomer();',
//            'changeTxt':'Change Customer Account',
//            'changeFn':'M.ciniki_sponsors_main.sponsor.changeCustomer();',
            },
        'contacts':{'label':'Employees', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.sponsors', 0x04); },
            'addTxt':'Add Employee',
            'addFn':'M.ciniki_sponsors_main.sponsor.addContact();',
            'editFn':function(s,i,d) {
                if( d != null ) {
                    return 'M.ciniki_sponsors_main.sponsor.updateContactLabel(' + i + ',\'' + escape(d.label) + '\');';
                }
                return '';
                },
            'deleteFn':function(s,i,d) {
                if( d != null ) {
                    return 'M.ciniki_sponsors_main.sponsor.deleteContact(' + i + ');';
                }
                return '';
                },
            },
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'website', 
            'visible':function() {return M.modFlagSet('ciniki.sponsors', 0x04); },
            'tabs':{
                'sponsorships':{'label':'Sponsorships', 'fn':'M.ciniki_sponsors_main.sponsor.switchTab("sponsorships");'},
                'items':{'label':'In Kind', 'fn':'M.ciniki_sponsors_main.sponsor.switchTab("items");'},
                'website':{'label':'Website', 'fn':'M.ciniki_sponsors_main.sponsor.switchTab("website");'},
            }},
        'sponsorships':{'label':'Sponsorships', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.ciniki_sponsors_main.sponsor.sections._tabs.selected == 'sponsorships' ? 'yes' : 'hidden'; },
            'headerValues':['Date', 'Package', 'Attached to', 'Amount'],
            'noData':'No sponsorships',
            },
        'donateditems':{'label':'Donated Items', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_sponsors_main.sponsor.sections._tabs.selected == 'items' ? 'yes' : 'hidden'; },
            'headerValues':['Donated', 'Item', 'Value', 'Sold Amount', 'Event'],
            'noData':'No donated items',
            },
        '_image':{'label':'Sponsor Logo', 'type':'imageform', 
            'visible':function() { return M.ciniki_sponsors_main.sponsor.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        '_excerpt':{'label':'Description', 
            'visible':function() { return M.ciniki_sponsors_main.sponsor.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'excerpt':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
            }},
        '_notes':{'label':'Notes', 
            'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sponsors_main.sponsor.save();'},
            'pdf':{'label':'Sponsor PDF', 'fn':'M.ciniki_sponsors_main.sponsor.downloadPDF();',
                'visible':function() { return M.modFlagOn('ciniki.sponsors', 0x04) && M.ciniki_sponsors_main.sponsor.sponsor_id > 0 ? 'yes' : 'no'; },
                },
            'delete':{'label':'Delete', 'fn':'M.ciniki_sponsors_main.sponsor.remove();'},
            }},
        };
    this.sponsor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.sponsor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sponsors.sponsorHistory', 'args':{'tnid':M.curTenantID, 
            'sponsor_id':this.sponsor_id, 'field':i}};
    }
    this.sponsor.liveSearchCb = function(s, i, value) {
        if( i == 'title' ) {
            M.api.getJSONBgCb('ciniki.sponsors.sponsorSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':value, 'limit':15}, 
                function(rsp) {
                    M.ciniki_sponsors_main.sponsor.liveSearchShow(s, i, M.gE(M.ciniki_sponsors_main.sponsor.panelUID + '_' + i), rsp.sponsors); 
                });
        }
    }
    this.sponsor.liveSearchResultValue = function(s, f, i, j, d) {
        return d.title;
    }
    this.sponsor.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( f == 'title' ) {
            return 'M.ciniki_sponsors_main.sponsor.open(null,\'' + d.id + '\');';
        }
    }
    this.sponsor.addDropImage = function(iid) {
        M.ciniki_sponsors_main.sponsor.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.sponsor.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.sponsor.editCustomer = function() {
        this.save("M.startApp('ciniki.customers.edit',null,'M.ciniki_sponsors_main.sponsor.open();','mc',{'next':'M.ciniki_sponsors_main.sponsor.updateCustomer', 'action':'edit', 'customer_id':M.ciniki_sponsors_main.sponsor.data.customer_id});");
    }
    this.sponsor.changeCustomer = function() {
        this.save("M.startApp('ciniki.customers.edit',null,'M.ciniki_sponsors_main.sponsor.open();','mc',{'next':'M.ciniki_sponsors_main.sponsor.updateCustomer', 'action':'change', 'current_id':M.ciniki_sponsors_main.sponsor.data.customer_id,'customer_id':0});");
    }
    this.sponsor.updateCustomer = function(cid) {
        M.api.getJSONCb('ciniki.sponsors.sponsorUpdate', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'customer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_sponsors_main.sponsor.open();
            });
    }
    this.sponsor.addContact = function() {
        this.save("M.startApp('ciniki.customers.edit',null,'M.ciniki_sponsors_main.sponsor.open();','mc',{'next':'M.ciniki_sponsors_main.sponsor.updateContact', 'action':'change', 'current_id':0,'customer_id':0});");
    }
    this.sponsor.deleteContact = function(i) {
        var contact = this.data.contacts[i];
        M.confirm("Are you sure you want to " + contact.display_name + " as an employee?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.contactDelete', {'tnid':M.curTenantID, 'contact_id':contact.id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    delete(M.ciniki_sponsors_main.sponsor.data.contacts[i]);
                    M.ciniki_sponsors_main.sponsor.refreshSection('contacts'); 
                });
            });
    }
    this.sponsor.updateContactLabel = function(i, old) {
        var contact = this.data.contacts[i];
        M.prompt('Employee Label:', unescape(old), 'Update', function(n) {
            if( old != n ) {
                M.api.getJSONCb('ciniki.sponsors.contactUpdate', {'tnid':M.curTenantID, 'contact_id':contact.id, 'label':n}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sponsors_main.sponsor.data.contacts[i].label = n;
                    M.ciniki_sponsors_main.sponsor.refreshSection('contacts'); 
                });
            }
            });
    }
    this.sponsor.updateContact = function(cid) {
        M.api.getJSONCb('ciniki.sponsors.contactAdd', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'customer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_sponsors_main.sponsor.open();
            });
    }
    this.sponsor.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'contacts' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.label;
            }
        }
        if( s == 'sponsorships' ) {
            switch(j) {
                case 0: return d.invoice_date;
                case 1: return d.name;
                case 2: return d.attached_to;
                case 3: return M.formatDollar(d.total_amount);
            }
        }
        if( s == 'donateditems' ) {
            switch(j) {
                case 0: return d.donated_date;
                case 1: return d.name;
                case 2: return d.value_amount;
                case 3: return d.sold_amount;
                case 4: return d.event_name;
            }
        }
    }
    this.sponsor.rowFn = function(s, i, d) {
        if( s == 'contacts' ) {
            return 'M.ciniki_sponsors_main.sponsor.save("M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sponsors_main.sponsor.open();\',\'mc\',{\'action\':\'edit\',\'customer_id\':' + d.customer_id + '});");';
        }
        return '';
    }
    this.sponsor.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSections(['_tabs']);
        this.showHideSections(['sponsorships', 'donateditems', '_image', '_excerpt']);
    }
    this.sponsor.downloadPDF = function() {
        M.api.openFile('ciniki.sponsors.sponsorGet', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'output':'pdf', 'sponsorships':'yes', 'donateditems':'yes'});
    }
    this.sponsor.open = function(cb, sid, lid) {
        this.reset();
        if( sid != null ) { this.sponsor_id = sid; }
        var args = {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id};
        if( M.modFlagOn('ciniki.sponsors', 0x04) ) {
            args['sponsorships'] = 'yes';
            args['donateditems'] = 'yes';
        }
        M.api.getJSONCb('ciniki.sponsors.sponsorGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sponsors_main.sponsor;
            p.data = rsp.sponsor;
            if( rsp.sponsor.customer_id > 0 ) {
                p.customer_id = rsp.sponsor.customer_id;
                p.sections.customer_details.addTxt = 'Edit Customer Account';
                p.sections.customer_details.changeTxt = 'Change Customer Account';
            } else {
                p.customer_id = 0;
                p.sections.customer_details.addTxt = 'Link Customer Account';
                p.sections.customer_details.changeTxt = '';
            }
            if( (M.curTenant.modules['ciniki.sponsors'].flags&0x01) > 0 ) { 
                p.sections.general.fields.level_id.options = {};
                for(i in rsp.levels) {
                    p.sections.general.fields.level_id.options[rsp.levels[i].id] = rsp.levels[i].name;
                }
                p.sections.general.fields.level_id.active = 'yes';
            } else {
                p.sections.general.fields.level_id.active = 'no';
            }
            p.sections._categories.fields.categories.list = rsp.categories;
            p.sections.customer_details.customer_id = rsp.sponsor.customer_id;
            p.refresh();
            p.show(cb);
            });
    }
    this.sponsor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_sponsors_main.sponsor.close();'; }
        if( this.sponsor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.sponsorUpdate', 
                    {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sponsors.sponsorAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_sponsors_main.sponsor.sponsor_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.sponsor.remove = function() {
        M.confirm("Are you sure you want to remove this sponsor?",null,function() {
            M.api.getJSONCb('ciniki.sponsors.sponsorDelete', 
                {'tnid':M.curTenantID, 
                'sponsor_id':M.ciniki_sponsors_main.sponsor.sponsor_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sponsors_main.sponsor.close();
                });
        });
    }
    this.sponsor.addButton('save', 'Save', 'M.ciniki_sponsors_main.sponsor.save();');
    this.sponsor.addClose('Cancel');

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

        //
        // Initialize for tenant
        //
        if( this.curTenantID == null || this.curTenantID != M.curTenantID ) {
            this.tenantInit();
            this.curTenantID = M.curTenantID;
        }

        //
        // Check if levels or categories are enabled
        //
        if( M.modFlagAny('ciniki.sponsors', 0x05) == 'yes' ) {
            this.sponsors.size = 'full narrowaside';
            if( M.modFlagOn('ciniki.sponsors', 0x05) ) {
                this.sponsors.sections.levels.label = '';
            } else if( M.modFlagOn('ciniki.sponsors', 0x01) ) {
                this.sponsors.sections._tabs.selected = 'levels';
                this.sponsors.sections.levels.label = 'Levels';
            } else if( M.modFlagOn('ciniki.sponsors', 0x04) ) {
                this.sponsors.sections.categories.label = 'Categories';
                this.sponsors.sections._tabs.selected = 'categories';
            }
        } else {
            this.sponsors.size = 'medium';
        }
        this.sponsor.size = 'medium mediumaside';
        if( M.modOn('ciniki.iks') ) {
            this.sponsor.size = 'large mediumaside';
        }

        if( args.sponsor_id != null ) {
            this.sponsor.open(cb, args.sponsor_id);
        } else {
            this.sponsors.open(cb, 0, 'Sponsors');
        }
    }

    this.tenantInit = function() {
        this.sponsors.level_id = 0;
        this.sponsors.category_id = 0;
        if( M.modFlagOn('ciniki.sponsors', 0x04) ) {
            this.sponsor.sections._tabs.selected = 'items';
            this.sponsors.sections.sponsors.num_cols = 4;
            this.sponsors.sections.sponsors.headerValues = ['Sponsor', 'Sponsorships', 'In Kind Value', 'Sold For'];
            this.sponsors.sections.sponsors.headerClasses = ['', 'alignright', 'alignright', 'alignright'];
            this.sponsors.sections.sponsors.cellClasses = ['multiline', 'alignright', 'alignright', 'alignright'];
            this.sponsors.sections.sponsors.footerClasses = ['', 'alignright', 'alignright', 'alignright'];
            this.sponsors.sections.sponsors.sortable = 'yes';
            this.sponsors.sections.sponsors.sortTypes = ['text', 'number', 'number', 'number'];
        } else {
            this.sponsor.sections._tabs.selected = 'website';
            this.sponsors.sections.sponsors.num_cols = 1;
            this.sponsors.sections.sponsors.headerValues = [];
            this.sponsors.sections.sponsors.cellClasses = ['multiline'];
            this.sponsors.sections.sponsors.footerValues = [''];
        }
    }
};
