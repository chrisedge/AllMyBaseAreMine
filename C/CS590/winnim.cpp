/*----------------------------------------------------
   Winnimcpp -- a little program playing the game of nim
   
  ----------------------------------------------------*/

#include "nim.h"
#include "winnim.h"
#include <windows.h>
#include <stdlib.h>

#define ID_TIMER 1

#define min(a,b) (((a) < (b)) ? (a) : (b))
#define max(a,b) (((a) > (b)) ? (a) : (b))

Nim theGame;
NimView theView(theGame);

char helpGameMsg[]=
"You and the computer alternate moves.  A move consists of selecting one row and removing from 1 to max (default 3) tokens.  The game is over when the last token is taken.";
char helpMoveMsg[]=
"You select a token by clicking on the token with the left mouse button.  (You may deselect by clicking again.) To remove the selected tokens, click with the right mouse button.  The computer's move is then shown in red.";

long FAR PASCAL _export WndProc (HWND, UINT, UINT, LONG) ;
BOOL FAR PASCAL _export ParmsDlgProc(HWND,UINT,UINT,LONG);

int PASCAL WinMain (HANDLE hInstance, HANDLE hPrevInstance,
                    LPSTR lpszCmdLine, int nCmdShow)
{
  static char szAppName[] = "Winnim" ;
  HWND        hwnd ;
  MSG         msg ;
  WNDCLASS    wndclass ;

  if (!hPrevInstance) {
     wndclass.style         = CS_HREDRAW | CS_VREDRAW ;
     wndclass.lpfnWndProc   = WndProc ;
     wndclass.cbClsExtra    = 0 ;
     wndclass.cbWndExtra    = 0 ;
     wndclass.hInstance     = hInstance ;
     wndclass.hIcon         = LoadIcon (NULL, IDI_APPLICATION) ;
     wndclass.hCursor       = LoadCursor (NULL, IDC_ARROW) ;
     wndclass.hbrBackground = GetStockObject (WHITE_BRUSH) ;
     wndclass.lpszMenuName  = szAppName ;
     wndclass.lpszClassName = szAppName ;

     RegisterClass (&wndclass) ;
   }

   hwnd = CreateWindow (szAppName, "Nim",
                        WS_OVERLAPPEDWINDOW,
                        CW_USEDEFAULT, CW_USEDEFAULT,
                        CW_USEDEFAULT, CW_USEDEFAULT,
                        NULL, NULL, hInstance, NULL) ;

   ShowWindow (hwnd, nCmdShow) ;
   UpdateWindow (hwnd) ;

   while (GetMessage (&msg, NULL, 0, 0)) {
     TranslateMessage (&msg) ;
     DispatchMessage (&msg) ;
   }

   return msg.wParam ;
}

void showGameOverMsg(HWND,int);

long FAR PASCAL _export WndProc (HWND hwnd, UINT message, UINT wParam,
                                                          LONG lParam)
{
   static short cxClient, cyClient;
   static HANDLE hInstance;
   static FARPROC lpfnParmsDlgProc;
   POINT p;
 //  HDC hdc ;
   PAINTSTRUCT  ps ;
   HMENU hMenu;
   RECT r;

   switch (message) {

     case WM_CREATE:
       hInstance=((LPCREATESTRUCT)lParam)->hInstance;
       lpfnParmsDlgProc=MakeProcInstance((FARPROC)ParmsDlgProc,hInstance);
       return(0);

     case WM_SIZE:
       cxClient = LOWORD (lParam) ;
       cyClient = HIWORD (lParam) ;
       theView.setViewport(cxClient,cyClient);
       return 0 ;

     case WM_PAINT:
       BeginPaint (hwnd, &ps) ;
         theView.showGame(hwnd);
       EndPaint (hwnd, &ps) ;
       return 0 ;

     case WM_LBUTTONDOWN:
       p.x=LOWORD(lParam);
       p.y=HIWORD(lParam);
       theView.showPlayerSelection(hwnd,p);
       return(0);

     case WM_RBUTTONDOWN:
       int over=theView.showPlayerMove(hwnd);
       if (over)
         showGameOverMsg(hwnd,over);
       return(0);

     case WM_COMMAND:
       switch(wParam) {

	 case IDM_LAST_LOSES:
	 case IDM_COMPUTER_FIRST:
	 case IDM_NEW:
           if (wParam==IDM_LAST_LOSES || wParam==IDM_COMPUTER_FIRST) {
	     hMenu=GetMenu(hwnd);
             if (wParam==IDM_LAST_LOSES)
               if(theGame.toggleWinner())
	         CheckMenuItem(hMenu,wParam,MF_CHECKED);
               else
		 CheckMenuItem(hMenu,wParam,MF_UNCHECKED);
	     else
               if(theGame.toggleStart())
	         CheckMenuItem(hMenu,wParam,MF_UNCHECKED);
               else
		 CheckMenuItem(hMenu,wParam,MF_CHECKED);
	   }
	   if (!theGame.gameOver()) {
	     theGame.concede();
	     showGameOverMsg(hwnd,2);
           }
	   theView.resetGame();
	   InvalidateRect(hwnd,NULL,TRUE);
	   UpdateWindow(hwnd);
	   if (!theGame.playerFirstMove())
             theView.showMyMove(hwnd);
	   return(0);

	 case IDM_PARAMETERS:
	   if (DialogBox(hInstance,"Parameters",hwnd,lpfnParmsDlgProc)){
             GetClientRect(hwnd,&r);
             theView.setViewport(r.right,r.bottom);
	     SendMessage(hwnd,WM_COMMAND,IDM_NEW,0);
           }
	   return(0);

	 case IDM_CONCEDE:
	   theGame.concede();
	   showGameOverMsg(hwnd,2);
	   return(0);

         case IDM_HELP_GAME:
	   MessageBox(hwnd,helpGameMsg,"About Nim",MB_OK);
	   return(0);

         case IDM_HELP_MOVE:
	   MessageBox(hwnd,helpMoveMsg,"How to move",MB_OK);
           return(0);

       } // end of WM_COMMAND
       break;

       case WM_DESTROY:
         KillTimer(hwnd,1);
         theView.deleteBrushes();
         PostQuitMessage (0) ;
         return 0 ;
     } // end of the main switch(msg)

   return DefWindowProc (hwnd, message, wParam, lParam) ;
}

BOOL FAR PASCAL _export ParmsDlgProc(HWND hDlg,UINT message,
				     UINT wParam,LONG lParam)
/* Dialog procedure for retrieval of game parameters.
*/
{  int pileCount;
   int piles[MAX_PILES];
   int maxCount;
   char buffer[60];
   static HWND hPileCountCtrl;
   static HWND hPileNumbersCtrl;
   static HWND hMaxMoveCtrl;
   switch(message) {

     case WM_INITDIALOG:
       hPileCountCtrl=GetDlgItem(hDlg,IDD_PILE_COUNT);
       hPileNumbersCtrl=GetDlgItem(hDlg,IDD_PILE_NUMBERS);
       hMaxMoveCtrl=GetDlgItem(hDlg,IDD_MAX_MOVE);
       theGame.getParameters(piles,&pileCount,&maxCount);
       wsprintf(buffer,"%d",pileCount);
       SetWindowText(hPileCountCtrl,buffer);
       wsprintf(buffer,"%d",maxCount);
       SetWindowText(hMaxMoveCtrl,buffer);
       char *s=buffer;
       for (int i=0;i<pileCount;i++) {
	 int n=wsprintf((LPSTR)s,"%d ",piles[i]);
	 s+=n;
       }
       SetWindowText(hPileNumbersCtrl,buffer);
       return(TRUE);

     case WM_COMMAND:

       switch(wParam) {
	 case IDD_CANCEL:
	   EndDialog(hDlg,0);
	   return(TRUE);

	 case IDD_OK:
	   GetWindowText(hPileCountCtrl,buffer,60);
	   pileCount=atoi(buffer);
	   if (pileCount<2 || pileCount>MAX_PILES) {
	     EndDialog(hDlg,0);
	     return(TRUE);
	   }
	   GetWindowText(hPileNumbersCtrl,buffer,60);
	   for (s=buffer,i=0;i<pileCount;i++) {
	     piles[i]=(int)strtol(s,&s,10);
	     if (piles[i]<1 || piles[i]>MAX_TOKENS) {
               EndDialog(hDlg,0);
	       return(TRUE);
             }
	   }
	   GetWindowText(hMaxMoveCtrl,buffer,80);
	   maxCount=atoi(buffer);
           if (maxCount<3 || maxCount>MAX_TOKENS) {
	     EndDialog(hDlg,0);
	     return(TRUE);
	   }
	   theGame.setParameters(piles,pileCount,maxCount);
	   EndDialog(hDlg,1);
	   return(TRUE);

	 default:
	   return(FALSE);
       } // END OF SWITCH wParam
     } // end of switch message
   return(FALSE);
}

void showGameOverMsg(HWND hwnd,int winner)
{ static int playerWinCount,computerWinCount;
  static char playerWonCaption[]="You won!!";
  static char computerWonCaption[]="Computer won.";
  char szOverMsg[80];
  char *szMsgCaption;
  if (winner==1) {
    playerWinCount++;
    szMsgCaption=playerWonCaption;
  }
  else {
    computerWinCount++;
    szMsgCaption=computerWonCaption;
  }
  wsprintf(szOverMsg,"You %d\t\t\tComputer %d",playerWinCount,
           computerWinCount);
  MessageBox(hwnd,szOverMsg,szMsgCaption,MB_OK);
}
