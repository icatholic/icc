Ext.define('icc.view.idatabase.Collection.Dashboard', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.idatabaseCollectionDashboard',
	frame: false,
	border: false,
	resizeTabs: false,
	enableTabScroll: true,
	layout: {
		type: 'table',
		columns: 3,
		tableAttrs: {
			style: {
				width: '100%'
			}
		},
		tdAttrs: {
			style: 'padding: 10px;'
		}
	},
	defaults: {
		frame: false,
		height: 350
	},
	renderLayout: false,
	renderDashboard: true,
	initComponent: function() {
		Ext.apply(this, {});
		this.callParent(arguments);
	},
	listeners: {
		afterlayout: function(panel) {
			if (!panel.renderLayout) {
				var columns = 3;
				var width = panel.getWidth();
				if (width >= 1000) {
					columns = 3;
				} else if (width >= 400 && width < 1000) {
					columns = 2;
				} else {
					columns = 1;
				}
				panel.layout.columns = columns;
				panel.renderLayout = true;
				panel.renderDashboard = false;
				panel.doLayout();
				return true;
			}

			if (!panel.renderDashboard) {
				panel.renderDashboard = true;
				var columns = panel.layout.columns;
				var width = Math.floor((panel.getWidth() - 50) / columns);
				Ext.Ajax.request({
					url: '/idatabase/dashboard/index',
					params: {
						__PROJECT_ID__: panel.__PROJECT_ID__
					},
					success: function(response) {
						var result = Ext.JSON.decode(response.responseText);
						if (Ext.isArray(result)) {
							Ext.Array.forEach(result, function(items, index, allTtems) {
								if (Ext.isArray(items['__DATAS__'])) {

									var store = Ext.create('Ext.data.Store', {
										fields: ["_id", "value"],
										data: items['__DATAS__']
									});
									var title = items.name;
									var seriesType = items.seriesType;
									var yAxisTitle = items.yAxisTitle;
									var yAxisType = items.yAxisType;
									var xAxisTitle = items.xAxisTitle;
									var type = {
										sum: '求和',
										avg: '均值',
										count: '计数',
										max: '最大值',
										min: '最小值',
										unique: '唯一值',
										median: '中位数',
										variance: '方差',
										standard: '标准差'
									};

									if (seriesType !== 'pie') {
										var chart = Ext.create('Ext.chart.Chart', {
											style: 'background:#fff',
											store: store,
											width: width,
											height: 300,
											axes: [{
												type: 'Numeric',
												minimum: 0,
												position: 'left',
												fields: ['value'],
												title: yAxisTitle,
												minorTickSteps: 1,
												grid: {
													odd: {
														opacity: 1,
														fill: '#ddd',
														stroke: '#bbb',
														'stroke-width': 0.5
													}
												}
											}, {
												type: 'Category',
												position: 'bottom',
												fields: ['_id'],
												title: xAxisTitle
											}],
											series: [{
												type: seriesType,
												axis: 'left',
												highlight: false,
												xField: '_id',
												yField: 'value',
												tips: {
													trackMouse: true,
													width: 'auto',
													height: 30,
													minHeight: 30,
													renderer: function(storeItem, item) {
														this.setTitle(storeItem.get('_id') + '的' + type[yAxisType] + ':' + storeItem.get('value'));
													}
												}
											}]
										});
									} else {
										var chart = Ext.create('Ext.chart.Chart', {
											animate: true,
											store: store,
											title: items.name,
											shadow: true,
											width: width,
											height: 300,
											legend: {
												position: 'right'
											},
											insetPadding: 10,
											theme: 'Base:gradients',
											series: [{
												type: 'pie',
												field: 'value',
												showInLegend: true,
												donut: true,
												tips: {
													trackMouse: true,
													width: 150,
													height: 28,
													renderer: function(storeItem, item) {
														var total = 0;
														store.each(function(rec) {
															total += rec.get('value');
														});
														this.setTitle(storeItem.get('_id') + ': ' + Math.round(storeItem.get('value') / total * 100, 2) + '%');
													}
												},
												highlight: {
													segment: {
														margin: 20
													}
												},
												label: {
													field: '_id',
													display: 'rotate',
													contrast: true,
													font: '18px Arial'
												}
											}]
										});
									}
									panel.add({
										title: title,
										xtype: 'panel',
										width: width,
										height: 350,
										items: [chart]
									});
								}
							});
						}
					}
				});
			}
			return false;
		}
	}
});