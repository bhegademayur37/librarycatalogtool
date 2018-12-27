import dryscrape
from bs4 import BeautifulSoup
from time import sleep
import mysql.connector
import sys
try:
   mydb = mysql.connector.connect(host='localhost',user='root',passwd='root123',db='isbn_search')
   cursor = mydb.cursor()
  
   read = str(sys.argv[1])
   print(read)
   #read = str(input("enter the isbn number:"))
   #reader=["9350293471","9388369157","9385724060","9386797186","9386228343","9381626685","9385724061"]
   #Titledb = Authordb = Pagesdb = Publisherdb = Languagedb = ISBN1db = ISBN2db = None
   #for read in reader:
   
   Titledb = Authordb = Pagesdb = Publisherdb = Languagedb = ISBN1db = ISBN2db ="NULL"
   url = "https://www.amazon.in/dp/" + read
   print(url)
   dryscrape.start_xvfb() 
   session = dryscrape.Session()
   session.visit(url)
   #sleep(2)
   response = session.body()
   soup = BeautifulSoup(response, "lxml")
  
   
   try:
           extract_title = soup.find('span', {'id': 'productTitle'})
           Title = extract_title.get_text()
           if Title:
               print("Book Title:", Title)
               Titledb = Title
           else:
               pass
   except:
           pass
   try:
           extract_author = soup.find('a', {'class': 'a-link-normal contributorNameID'})
           Author = extract_author.get_text()
           if Author:
               print("Book Author:", Author)
               Authordb = Author
           else:
               pass
   except:
           pass
   try:
           extract = soup.find('div', {'id': 'detail_bullets_id'})
           extract_detail = extract.find_all('li')[0:7]
           Pages = extract_detail[0].text
           if Pages:
               print("Book", Pages)
               if Pages.split(":")[0] in ("Hardcover" , "Paperback"):
                   #print(Pages.split(":")[0])
                   Pagesdb = Pages.split(":")[1]
               else:
                   Pagesdb = " "
           else:
               pass
           Publisher = extract_detail[1].text
           if Publisher:
               print("Book", Publisher)
               if Publisher.split(":")[0] == "Publisher":
                   Publisherdb = Publisher.split(":")[1]
               else:
                   Publisherdb = " "
           else:
               pass
           Language = extract_detail[2].text
           if Language:
               print("Book", Language)
               if Language.split(":")[0] == "Language":
                   Languagedb = Language.split(":")[1]
               else:
                   Languagedb = " "
           else:
               pass
           ISBN1 = extract_detail[3].text
           if ISBN1:
               print(ISBN1)
               if ISBN1.split(":")[0] == "ISBN-10":
                   ISBN1db = ISBN1.split(":")[1]
               else:
                   ISBN1db = " "
           else:
               pass
           ISBN2 = extract_detail[4].text
           if ISBN2:
               print(ISBN2)
               if ISBN2.split(":")[0] == "ISBN-13":
                   ISBN2db = ISBN2.split(":")[1]
               else:
                   ISBN2db = " "
           else:
               pass
   except Exception as e:
           pass
   #print("Preparing for inserting values in mysql")
   if Titledb == "NULL":
           pass
   else:
           cursor.execute('INSERT  INTO amazon_data(title,author,pages,publisher,language,isbn_10,isbn_13)''VALUES("%s", "%s", "%s","%s","%s","%s","%s")' % (Titledb, Authordb, Pagesdb, Publisherdb, Languagedb, ISBN1db, ISBN2db))
           print("Inserted into mysql")
  # Titledb = Authordb = Pagesdb = Publisherdb = Languagedb = ISBN1db = ISBN2db = None
       #Titledb,Authordb,Pagesdb,Publisherdb,Languagedb,ISBN1db,ISBN2db = ""
except Exception as e:
      print("connection refused")
      print(e)
mydb.commit()
# close the connection to the database.
cursor.close()
#ALTER TABLE amazon_data ADD  VARCHAR( 255 ) after q5


