Ext.define('icc.view.idatabase.Project.Accordion', {
    extend: 'Ext.panel.Panel',
    xtype: 'idatabaseProjectAccordion',
    region: 'west',
    layout: 'accordion',
    width: 400,
    title: '功能列表',
    resizable: false,
    collapsible: true,
    initComponent: function() {
        var items = [{
            xtype: 'idatabaseProjectGrid',
            title: '项目管理',
            isSystem : false
        }];

        if (typeof(isSystem) !== 'undefined') {
            items.push({
                xtype: 'idatabaseProjectGrid',
                title: '系统管理',
                isSystem: true
            });
        }
        Ext.apply(this, {
            items: items
        });

        this.callParent(arguments);
    }
});