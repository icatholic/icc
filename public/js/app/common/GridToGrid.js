Ext.define('icc.common.GridToGrid', {
	extend : 'Ext.container.Container',
	requires : [ 'Ext.grid.*', 'Ext.layout.container.HBox'],
	width: 650,
    height: 300,
    layout: {
        type: 'hbox',
        align: 'stretch',
        padding: 5
    },
    dragStore : '',
    dropValue : '',
    initComponent: function(){
        var groupDrag = this.id + 'Drag';
        var groupDrop = this.id + 'Drop';
        
        if(Ext.isString(this.dropValue)) {
        	this.dropValue = Ext.Json.decode(this.dropValue,true);
        }
        
        Ext.apply(this,{
        	items : [{
        		title: '数据来源',
    	        itemId: 'grid1',
    	        flex: 1,
    	        xtype: 'grid',
    	        multiSelect: true,
    	        viewConfig: {
    	            plugins: {
    	                ptype: 'gridviewdragdrop',
    	                dragGroup: groupDrag,
    	                dropGroup: groupDrop
    	            },
    	            listeners: {
    	                drop: function(node, data, dropRec, dropPosition) {
    	                    var dropOn = dropRec ? ' ' + dropPosition + ' ' + dropRec.get('name') : ' on empty view';
    	                    Ext.example.msg('Drag from right to left', 'Dropped ' + data.records[0].get('name') + dropOn);
    	                }
    	            }
    	        },
    	        store: this.dragStore,
    	        columns: columns,
    	        stripeRows: true,
    	        tbar : [{
					xtype: 'searchBar',
					store: store
				}],
				bbar: {
    				xtype: 'paging',
    				store: store
    			},
    	        margins: '0 5 0 0'
    	    }, {
    	    	title: '关联数据',
    	        itemId: 'grid2',
    	        flex: 1,
    	        xtype: 'grid',
    	        viewConfig: {
    	            plugins: {
    	                ptype: 'gridviewdragdrop',
    	                dragGroup: groupDrag,
    	                dropGroup: groupDrop
    	            },
    	            listeners: {
    	                drop: function(node, data, dropRec, dropPosition) {
    	                    var dropOn = dropRec ? ' ' + dropPosition + ' ' + dropRec.get('name') : ' on empty view';
    	                    Ext.example.msg('Drag from left to right', 'Dropped ' + data.records[0].get('name') + dropOn);
    	                }
    	            }
    	        },
    	        store: this.dragStore,
    	        columns: columns,
    	        stripeRows: true
    	    }]
        });
	    this.callParent();
    }
});