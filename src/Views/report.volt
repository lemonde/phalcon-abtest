<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AB Testing report</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.css">
</head>
<body style="margin: 0">

<form style="padding: 1em; border-bottom: 2px solid gray; background: #eee;">
    <b>View results for</b> :
    <select name="test">
        {% for test in tests %}
            <option {{ current_test == test ? 'selected':'' }}>{{ test }}</option>
        {% endfor %}
    </select>
    on
    <select name="device">
        <option {{ current_device == '*' ? 'selected':'' }} value="*">all devices</option>
        <option {{ current_device == 'desktop' ? 'selected':'' }}>desktop</option>
        <option {{ current_device == 'tablet' ? 'selected':'' }}>tablet</option>
        <option {{ current_device == 'mobile' ? 'selected':'' }}>mobile</option>
    </select>
    for
    <select name="type">
        <option {{ current_type == '10m' ? 'selected':'' }} value="10m">the last 10 minutes</option>
        <option {{ current_type == 'hour' ? 'selected':'' }} value="hour">the last hour</option>
        <option {{ current_type == 'day' ? 'selected':'' }} value="day">the last day</option>
        <option {{ current_type == 'month' ? 'selected':'' }} value="month">the last month</option>
        <option {{ current_type == 'total' ? 'selected':'' }} value="total">everyting</option>
    </select>
    repeated <input type="number" name="range" value="{{ current_range }}" min="1" max="1000"> times
    <button type="submit">Go to</button>
</form>

<div style="margin: 1em">
    <table id="mytable" class="display" data-order="{{ '[[ 0, "desc" ]]'|escape }}" data-page-length="10">
        <thead><tr>
            <th>Date</th>
            <th>Device</th>
            <th>Test Name</th>
            <th>Template</th>
            <th>Impression</th>
            <th>Click</th>
            <th>CTR</th>
        </tr></thead>
        <tbody>
        {% for row in data %}
            <tr>
                <td data-order="{{ row['date'] }}">{{ row['header'] }}</td>
                <td>{{ row['device'] }}</td>
                <td>{{ row['testName'] }}</td>
                <td>{{ row['template'] }}</td>
                <td>{{ row['impression'] }}</td>
                <td>{{ row['click'] }}</td>
                <td>{{ row['ctr'] }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/v/dt/jq-2.2.4/dt-1.10.20/datatables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#mytable').DataTable();
  });
</script>
</body>
</html>
