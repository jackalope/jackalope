Search
======

Basic Search using a JRC-SQL2 Query
----

### Step by Step
Queries are executed on the Query Manager, you can get the instance through the Jackalope Session:

    $queryManager = $session->getWorkspace()->getQueryManager();


To query the note simply call createQuery with the Query on the queryManager. It will return a query instance.

    $query = $queryManager->createQuery("SELECT * FROM [nt:unstructured]", 'JCR-SQL2');


Then Execute on the query to recieve the queryResult instance.

    $queryResult = $this->query->execute();


###All in one:

    $queryManager = $session->getWorkspace()->getQueryManager();
    $query = $queryManager->createQuery("SELECT * FROM [nt:unstructured]", 'JCR-SQL2');
    $queryResult = $this->query->execute();

Iterate over the Search Result
----

Once you have your queryResult you can iterate over it using foreach.

    foreach ($queryResult as $key => $row) {
        $row->getPath();
        $row->getNode();

        foreach ($row as $key => $value) { // Test if we can iterate over the columns inside a row
            $count++;
        }
    }

In each iteration you will get a new row object. On it you can call the following methods:

<table>
    <tbody>
        <tr>
            <td>$row->getValues()</td>
            <td>Returns an array with all columns as keys and their values</td>
        </tr>
        <tr>
            <td>$row->getValue($columnName)</td>
            <td>Returns the value of the Column $columnName</td>
        </tr>
        <tr>
            <td>$row->getNode()</td>
            <td>requests the Node that matched the search query</td>
        </tr>
        <tr>
            <td>$row->getPath()</td>
            <td>Returns the Path of the Node that matched the query</td>
        </tr>
        <tr>
            <td>$row->getScore()</td>
            <td>Returns the Score of the match</td>
        </tr>
    </tbody>
</table>

Each row itself can be iterated aswell to go through all columns of the row:

    foreach ($queryResult as $key => $row) {
        foreach ($row as $columName => $value) {
            echo $columName.':'.$value;
        }
    }

Iterate directly over the Nodes
----

As a shortcut you can directly iterate over the nodes of the queryResult

    foreach ($queryResult->getNodes() as $node) {
        $this->assertInstanceOf('Jackalope\Node', $node);
    }
