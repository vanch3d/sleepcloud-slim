/*!
 * d3.weeklyHeatMap v1.0
 */
d3.chart = d3.chart || {};

/**
 * Week (day/hour) Heat Map for d3.js (v4)
 *
 * Usage:
 *
 *  var chart = d3.chart.weekHeatMap();
 *  d3.select('#chart_placeholder')
 *      .datum(data)
 *      .call(chart);
 *
 *  // Data must be nested
 *  var data = {
 *  };
 *
 *  // You can customize the chart width, height and margin
 *  var chart = d3.chart.weeklyHeatMap()
 *                  .width(700)
 *                  .height(150)
 *                  .margin({ top: 50, right: 0, bottom: 100, left: 30 });
 *
 * @author vanch3d <nicolas.github@calques3d.org>
 * @license MIT
 * @see https://github.com/vanch3d/d3.weeklyHeatMap for complete source and license
 * @see http://bl.ocks.org/tjdecke/5558084 for original inspiration and source
 */
d3.chart.weeklyHeatMap= function(options) {

    var chartWidth = 960;
    var chartHeight = 430;
    var chartMargin = { top: 50, right: 0, bottom: 100, left: 30 };

    function chart(selection) {

        var colors = ["#ffffd9","#edf8b1","#c7e9b4","#7fcdbb","#41b6c4","#1d91c0","#225ea8","#253494","#081d58"], // alternatively colorbrewer.YlGnBu[9]
            days = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
            times = ["1a", "2a", "3a", "4a", "5a", "6a", "7a", "8a", "9a", "10a", "11a", "12a", "1p", "2p", "3p", "4p", "5p", "6p", "7p", "8p", "9p", "10p", "11p", "12p"];

        selection.each(function(data) {

            var width = chartWidth - chartMargin.left - chartMargin.right,
                height = chartHeight - chartMargin.top - chartMargin.bottom,
                gridSize = Math.floor(width / 24),
                legendElementWidth = gridSize*1.25,
                buckets = 9;

            var svg = d3.select(this).append("svg")
                .attr("width", width + chartMargin.left + chartMargin.right)
                .attr("height", height + chartMargin.top + chartMargin.bottom)
                .append("g")
                .attr("transform", "translate(" + chartMargin.left + "," + chartMargin.top + ")");

            var dayLabels = svg.selectAll(".dayLabel")
                .data(days)
                .enter().append("text")
                .text(function (d) { return d; })
                .attr("x", 0)
                .attr("y", function (d, i) { return i * gridSize; })
                .style("text-anchor", "end")
                .attr("transform", "translate(-6," + gridSize / 1.5 + ")")
                .attr("class", function (d, i) { return ((i >= 0 && i <= 4) ? "dayLabel mono axis axis-workweek" : "dayLabel mono axis"); });

            var timeLabels = svg.selectAll(".timeLabel")
                .data(times)
                .enter().append("text")
                .text(function(d) { return d; })
                .attr("x", function(d, i) { return i * gridSize; })
                .attr("y", 0)
                .style("text-anchor", "middle")
                .attr("transform", "translate(" + gridSize / 2 + ", -6)")
                .attr("class", function(d, i) { return ((i >= 7 && i <= 16) ? "timeLabel mono axis axis-worktime" : "timeLabel mono axis"); });

            var max = d3.max(data, function (d) {
                var maxval = d3.max(d.values,function(v){ return v.value; });
                return maxval;
            });
            console.log("max value: ",max);

            var colorScale = d3.scaleQuantile()
                .domain([0, buckets - 1, max])
                .range(colors);

            var dayRow = svg.selectAll(".day")
                .data(data)
                .enter()
                .append("g")
                .attr("transform", function(d){
                    console.log("cards key",d.key);
                    return "translate(0," + d.key * gridSize+ ")"
                });

            var cards = dayRow.selectAll(".hour")
                .data(function (d) { return d.values; })
                .enter()
                .append("rect")
                .attr("x", function(d) { return (d.key) * gridSize; })
                .attr("y", 0)
                .attr("rx", 4)
                .attr("ry", 4)
                .attr("class", "hour bordered")
                .attr("width", gridSize)
                .attr("height", gridSize)
                .style("fill", colors[0]);

            cards.transition().duration(750)
                .style("fill", function(d) { return colorScale(d.value); });

            cards.append("title")
                .text(function(d) { return d.value; });

            var legend = svg.selectAll(".legend")
                .data([0].concat(colorScale.quantiles()), function(d) { console.log(d);return d; })
                .enter()
                .append("g")
                .attr("class", "legend");

            legend.append("rect")
                .attr("x", function(d, i) { console.log("d",d);return legendElementWidth * i; })
                .attr("y", height)
                .attr("width", legendElementWidth)
                .attr("height", gridSize / 2)
                .style("fill", function(d, i) { return colors[i]; });

            legend.append("text")
                .attr("class", "mono")
                .text(function(d) { return "≥ " + Math.round(d); })
                .attr("x", function(d, i) { return legendElementWidth * i; })
                .attr("y", height + gridSize);
        });
    }

    chart.width = function(value) {
        if (!arguments.length) return chartWidth;
        chartWidth = value;
        return chart;
    };

    chart.height = function(value) {
        if (!arguments.length) return chartHeight;
        chartHeight = value;
        return chart;
    };

    chart.margin = function(value) {
        if (!arguments.length) return chartMargin;
        chartMargin = value;
        return chart;
    };

    return chart;


};
