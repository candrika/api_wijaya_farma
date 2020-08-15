Ext.define('GridTreeAccJurnal', {
    // title: 'Daftar Akun',
    // selModel : smGridIP,   
    itemId: 'GridTreeAccJurnal',
    id: 'GridTreeAccJurnal',
    extend: 'Ext.tree.Panel',
    alias: 'widget.GridTreeAccJurnal',
    xtype: 'tree-grid',
    store: storeAccountAktive,
    loadMask: true,
    // height: 300,
    useArrows: true,
    rootVisible: false,
    multiSelect: true,
    // singleExpand: true,
    expanded: true,
    columns: [{
            //we must use the templateheader component so we can use a custom tpl
            xtype: 'treecolumn',
            text: 'accnumber',
            minWidth: 200,
            sortable: true,
            dataIndex: 'accnumber'
        }, {
            xtype: 'treecolumn', //this is so we know which column will show the tree
            text: 'Nama Akun',
            // flex: 2,
            minWidth: 350,
            sortable: true,
            dataIndex: 'text'
        },  {
            //we must use the templateheader component so we can use a custom tpl
            xtype: 'numbercolumn',
            text: 'balance',
            align:'right',
            sortable: true,
            minWidth: 150,
            dataIndex: 'balance'
        }
    ]
    , dockedItems: [{
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    itemId: 'PilihAccJurnal',
                    text: 'Pilih Akun',
                    iconCls: 'add-icon',
                    handler: function() {
                        var grid = Ext.ComponentQuery.query('GridTreeAccJurnal')[0];
                        var selectedRecord = grid.getSelectionModel().getSelection()[0];
                        var data = grid.getSelectionModel().getSelection();
                        if (data.length == 0)
                        {
                            Ext.Msg.alert('Failure', 'Pilih Akun terlebih dahulu!');
                        } else {
//                            console.log(selectedRecord);
                            Ext.getCmp('accnamejurnal').setValue(selectedRecord.get('text'));
                            Ext.getCmp('idaccountjurnal').setValue(selectedRecord.get('id'));
                            Ext.getCmp('accnumberjurnal').setValue(selectedRecord.get('accnumber'));

                            Ext.getCmp('windowPopupAccJurnal').hide();
                        }


                    }
                },'->',
                {
                    xtype: 'textfield',
                    id: 'searchAccJurnal',
                    blankText:'Cari akun disini',
                    listeners: {
                        specialkey: function(f, e) {
                            if (e.getKey() == e.ENTER) {
                                storeAccount.load({
                                    params: {
                                        'accname': Ext.getCmp('searchAccJurnal').getValue(),
                                    }
                                });
                            }
                        }
                        // console.log(storeAccount)
                    }
                }, {
//                        itemId: 'reloadDataAcc',
                    text: 'Cari',
                    iconCls: 'add-icon'
                    , handler: function() {
                        storeAccount.load({
                            params: {
                                'accname': Ext.getCmp('searchAccJurnal').getValue(),
                            }
                        });
                    }
                }, '-', {
                    itemId: 'reloadDataAccJurnal',
                    text: 'Refresh',
                    iconCls: 'add-icon',
                    handler: function() {
                        var grid = Ext.getCmp('GridTreeAccJurnal');
                        grid.getView().refresh();
                        storeAccount.load();
                        Ext.getCmp('searchAccJurnal').setValue(null)
                    }
                }]
        }
    ]
    , listeners: {
        render: {
            scope: this,
            fn: function(grid) {
                Ext.getCmp('GridTreeAccJurnal').expandAll();
            }
        }
    }
});

Ext.define(dir_sys+'money.windowPopupAccPanjar', {
    id: 'windowPopupAccPanjar',
     title: 'Daftar Akun',
    header: {
        // titlePosition: 2,
        titleAlign: 'center'
    },
    closable: true,
    closeAction: 'hide',
    maximizable: true,
    autoWidth: true,
    minWidth: 750,
    height: 450,
    // x: 300,
    // y: 50,
    layout: 'fit',
    border: false,
    items: [
        Ext.create('Ext.panel.Panel', {
            bodyPadding: 5,  // Don't want content to crunch against the borders
            width: 500,
            height: 300,
            layout:'fit',
            id: 'tabAccTreeJurnal',
            items: [{
                xtype: 'GridTreeAccJurnal'
            }]
        })
    ],
    buttons: [{
            text: 'Tutup',
            handler: function() {
                var windowPopupAccJurnal = Ext.getCmp('windowPopupAccJurnal');
                windowPopupAccJurnal.hide();
            }
        }]
});