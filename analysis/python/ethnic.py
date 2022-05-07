import pandas as pd
import pymysql
import json

from pymysql.cursors import DictCursor


def connect():
    connection = pymysql.connect(

        # host='127.0.0.1',
        # user='root',
        # password='',
        # db='imdbvisualization',
        # charset='utf8mb4',

     #   host='127.0.0.1',
     #   user='reviewdakestfuho',
     #   password='NkqVAGxd',
     #   db='imdbvisualization',

        host='172.17.0.1:3307',
        user='root',
        password='3j23MjtXm-2nv_7XJR',
        db='imdbvisualization',
        cursorclass=DictCursor
    )
    return connection


def getdata():
    names = []
    connection = connect()
    with connection.cursor() as q:

        query = "SELECT  data_actors_imdb.name, data_actors_imdb.birth_name, data_actors_imdb.id  FROM data_actors_imdb  LEFT JOIN data_actors_meta ON data_actors_imdb.id=data_actors_meta.actor_id  WHERE data_actors_meta.`surname` IS NULL and  data_actors_imdb.`lastupdate` > 2   LIMIT 10000 "
        # print(query)
        q.execute(query)

        print('total select:', q.rowcount)

        for row in q:

            name_result = ''
            last_name_result = ''


            rid = row['id']
            name = row['name']
            bname = row['birth_name']
            bname = bname.replace('Jr.', '')
            bname = bname.replace('J.', '')
            bname = bname.replace('III', '')
            bname = bname.replace('II', '')
            bname = bname.replace(' IV', '')

            bname = bname.rstrip().lstrip()
            bname_lst = bname.split(' ')
            name_lst = name.split(' ')

            # print(name_lst)
            # print(bname_lst)

            name = name_lst[0]

            lastname = ''
            count = len(name_lst)
            if count > 2:
                lastname = name_lst[2]
            elif count > 1:
                lastname = name_lst[1]

            name_result = ''
            last_name_result = ''
            count = int(len(bname_lst))

            if count > 1:
                name_result = bname_lst[0]

                for i in bname_lst:
                    if i == lastname and lastname:
                        last_name_result = lastname

                if '' == last_name_result:
                    # print(count)
                    if count > 2:
                        i = int(2)
                        while i < count:
                            if i > 1:
                                last_name_result += ' ' + str(bname_lst[i])
                            i = i + 1
                    else:
                        if count > 1:
                            last_name_result = bname_lst[1]


            else:
                name_result = name
                last_name_result = lastname

            last_name_result = last_name_result.rstrip().lstrip()
            data = {'name': name_result, 'last': last_name_result, 'id': rid}
            # print(name_result+' '+last_name_result)
            names.append(data)
    connection.close()
    return names
def  updatemeta( id):
    connection = connect()

    with connection.cursor() as q:
        query = "UPDATE `data_actors_meta` SET `surname` = '1'  WHERE `data_actors_meta`.`actor_id` = " + str(id) + " "
        # print(query)
        q.execute(query)
        connection.commit()
        connection.close()


def updatedata(type, id, verdict, jsonStr, actor_name):
    connection = connect()

    with connection.cursor() as q:
        # chech data
        query = "        SELECT   id    FROM      data_actors_surname        where  actor_id=" + str(id) + "  limit 1  "
        # print(query)
        q.execute(query)
        #print(q.rowcount)

        if q.rowcount == 0:
            query = "INSERT INTO `data_actors_surname` (`id`,`actor_id`,`actor_name`) VALUES (NULL, '" + str(id) + "',   %s );"
            #print(query)
            array = (actor_name)
            q.execute(query, array)
            connection.commit()
        # update data

        query = "UPDATE `data_actors_surname` SET `" + type + "` = '" + str(
            verdict) + "', `" + type + "_data` =  %s  WHERE `data_actors_surname`.`actor_id` = " + str(id) + " "
        #print(query)

        array = (jsonStr)
        q.execute(query, array)
        connection.commit()
    connection.close()


names = getdata()

#print(names)

# help(pred_wiki_name)

if (names):

    pd.set_option('display.max_rows', None)
    pd.set_option('display.max_columns', None)
    pd.set_option('display.width', None)
    pd.set_option('display.max_colwidth', -1)
    from ethnicolr import pred_census_ln, pred_wiki_name,  pred_fl_reg_name

    df = pd.DataFrame(names)

    # print(df)

    #/////////////////////////census////////////////////////
    result = pred_census_ln(df, 'last')
    dfx = result.values.tolist()
    dfx_c = [result.columns.values.tolist()]
    # print(names[2])
    #print(dfx_c)
    #print(dfx)
    count = int(len(dfx))
    i = 0
    jsonStr = json.dumps(dfx_c)
    updatedata('census', 0, ' ', jsonStr, ' ')

    while i < count:
        data = dfx[i]
        id = dfx[i][0]
        verdict = dfx[i][3]

        actor_name = dfx[i][2] + ' ' + dfx[i][1]

        #print(id)
        jsonStr = json.dumps(data)
        #print(str(jsonStr))
        updatedata('census', id, verdict, jsonStr, actor_name)
        updatemeta(id)
        i = i + 1


    #/////////////////////////wiki_name////////////////////////
    result = pred_wiki_name(df, 'last','name')

    dfx = result.values.tolist()
    dfx_c = [result.columns.values.tolist()]
    # print(names[2])
    #print(dfx_c)
    #print(dfx)
    count = int(len(dfx))
    i = 0
    jsonStr = json.dumps(dfx_c)
    updatedata('wiki', 0, ' ', jsonStr, ' ')

    while i < count:
        data = dfx[i]
        id = dfx[i][0]
        verdict = dfx[i][3]

        actor_name = dfx[i][2] + ' ' + dfx[i][1]

        #print(id)
        jsonStr = json.dumps(data)
        #print(str(jsonStr))
        updatedata('wiki', id, verdict, jsonStr, actor_name)
        i = i + 1

    #/////////////////////////fl_reg////////////////////////
    result = pred_fl_reg_name(df, 'last','name')

    dfx = result.values.tolist()
    dfx_c = [result.columns.values.tolist()]
    # print(names[2])
    #print(dfx_c)
    #print(dfx)
    count = int(len(dfx))
    i = 0
    jsonStr = json.dumps(dfx_c)
    updatedata('flreg', 0, ' ', jsonStr, ' ')

    while i < count:
        data = dfx[i]
        id = dfx[i][0]
        verdict = dfx[i][3]

        actor_name = dfx[i][2] + ' ' + dfx[i][1]

        #print(id)
        jsonStr = json.dumps(data)
        #print(str(jsonStr))
        updatedata('flreg', id, verdict, jsonStr, actor_name)
        i = i + 1


    print(count)

# print( pred_wiki_name(df, 'last','name'))
# print( pred_fl_reg_name(df, 'last','name'))
