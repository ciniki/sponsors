//
function ciniki_sponsors_settings() {
    //
    // The menu panel
    //
    this.menu = new M.panel('Settings', 'ciniki_sponsors_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.sponsors.settings.menu');
    this.menu.sections = {
        'packages':{'label':'Sponsorship Packages', 
            'visible':function() { return M.modFlagSet('ciniki.sponsors', 0x10); },
            'list':{
                'packages':{'label':'Packages', 'fn':'M.ciniki_sponsors_settings.packages.open(\'M.ciniki_sponsors_settings.menu.open();\');'},
            }},
    }
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The panel to list the package
    //
    this.packages = new M.panel('package', 'ciniki_sponsors_settings', 'packages', 'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.packages');
    this.packages.data = {};
    this.packages.nplist = [];
    this.packages.sections = {
        'packages':{'label':'Sponsor Package', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Category', 'Visible'],
            'noData':'No package',
            'addTxt':'Add Sponsor Package',
            'addFn':'M.ciniki_sponsors_settings.package.open(\'M.ciniki_sponsors_settings.packages.open();\',0,null);'
            },
    }
    this.packages.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.sponsors.packageSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_sponsors_settings.packages.liveSearchShow('search',null,M.gE(M.ciniki_sponsors_settings.packages.panelUID + '_' + s), rsp.packages);
                });
        }
    }
    this.packages.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.packages.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_sponsors_settings.package.open(\'M.ciniki_sponsors_settings.packages.open();\',\'' + d.id + '\');';
    }
    this.packages.cellValue = function(s, i, j, d) {
        if( s == 'packages' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.category;
                case 2: return d.visible;
            }
        }
    }
    this.packages.rowFn = function(s, i, d) {
        if( s == 'packages' ) {
            return 'M.ciniki_sponsors_settings.package.open(\'M.ciniki_sponsors_settings.packages.open();\',\'' + d.id + '\',M.ciniki_sponsors_settings.package.nplist);';
        }
    }
    this.packages.open = function(cb) {
        M.api.getJSONCb('ciniki.sponsors.packageList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sponsors_settings.packages;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.packages.addClose('Back');

    //
    // The panel to edit Sponsor Package
    //
    this.package = new M.panel('Sponsor Package', 'ciniki_sponsors_settings', 'package', 'mc', 'medium', 'sectioned', 'ciniki.sponsors.main.package');
    this.package.data = null;
    this.package.package_id = 0;
    this.package.nplist = [];
    this.package.sections = {
/*        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_sponsors_settings.package.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }}, */
        'general':{'label':'Sponsorship Package', 'fields':{
            'invoice_code':{'label':'Code', 'type':'text'},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'invoice_name':{'label':'Invoice Description', 'required':'yes', 'type':'text'},
//            'subname':{'label':'', 'type':'text'},
//            'object':{'label':'', 'type':'text'},
//            'object_id':{'label':'', 'type':'text'},
            'attached_to':{'label':'Attached To', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'full_name'}},
            'category':{'label':'Accounting Category', 'type':'text'},
            'subcategory':{'label':'Sponsorship Category', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'flags1':{'label':'Visible', 'type':'flagtoggle', 'field':'flags', 'bit':0x01, 'default':'off'},
            'flags2':{'label':'Fixed Amount', 'type':'flagtoggle', 'field':'flags', 'bit':0x02, 'default':'on', 
                'on_fields':['amount'],
                },
            'amount':{'label':'Amount', 'type':'text', 'size':'medium'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'Synopsis', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sponsors_settings.package.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_sponsors_settings.package.package_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_sponsors_settings.package.remove();'},
            }},
        };
    this.package.fieldValue = function(s, i, d) { return this.data[i]; }
    this.package.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sponsors.packageHistory', 'args':{'tnid':M.curTenantID, 'package_id':this.package_id, 'field':i}};
    }
    this.package.open = function(cb, pid, list, obj, oid) {
        if( pid != null ) { this.package_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.sponsors.packageGet', {'tnid':M.curTenantID, 'package_id':this.package_id, 'object':obj, 'object_id':oid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sponsors_settings.package;
            p.data = rsp.package;
            p.sections.general.fields.amount.visible = ((rsp.package.flags&0x02) == 0x02 ? 'yes' : 'no');
            p.sections.general.fields.attached_to.options = rsp.objects;
//            p.sections.general.fields.attached_to.options.unshift({
//                'id':0,
//                'name':'None',
//                'full_name':'None',
//                });
            p.refresh();
            p.show(cb);
        });
    }
    this.package.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_sponsors_settings.package.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.package_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sponsors.packageUpdate', {'tnid':M.curTenantID, 'package_id':this.package_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.sponsors.packageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sponsors_settings.package.package_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.package.remove = function() {
        if( confirm('Are you sure you want to remove package?') ) {
            M.api.getJSONCb('ciniki.sponsors.packageDelete', {'tnid':M.curTenantID, 'package_id':this.package_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sponsors_settings.package.close();
            });
        }
    }
    this.package.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.package_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_sponsors_settings.package.save(\'M.ciniki_sponsors_settings.package.open(null,' + this.nplist[this.nplist.indexOf('' + this.package_id) + 1] + ');\');';
        }
        return null;
    }
    this.package.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.package_id) > 0 ) {
            return 'M.ciniki_sponsors_settings.package.save(\'M.ciniki_sponsors_settings.package.open(null,' + this.nplist[this.nplist.indexOf('' + this.package_id) - 1] + ');\');';
        }
        return null;
    }
    this.package.addButton('save', 'Save', 'M.ciniki_sponsors_settings.package.save();');
    this.package.addClose('Cancel');
    this.package.addButton('next', 'Next');
    this.package.addLeftButton('prev', 'Prev');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sponsors_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.package_id != null ) {
            this.package.open(cb, args.package_id, null, args.object, args.object_id);
        } else {
            this.menu.open(cb);
        }
    }
}
