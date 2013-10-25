Glossary for C-Shell
  
$ignoreeof: A built-in variable that changes the effect of the character
which indicates an end of a file (EOF). The default for this character is
^D, but it can be changed using the stty command. Normally, when the
C-shell receives an EOF character on stdin, it terminates. If it is the
login shell and EOF is entered, the shell exits and you are logged-- off
the system. Setting $ignoreeof forces you to use either exit or logout to
leave a shell process. This is a boolean/logical variable and is either
set or unset-does not take a value. 
  
$noclobber: A buit-in variable that prevents accidental overwriting of a
file when you redirect the output. When you use append redirection with
this variable set, the target file must exist or the operation will not
proceed. To override the effect of this variable, you must add an
exclamation mark (!) after the redirection metacharacters. This is a
boolean/logical variable, and is either is set or unset-no value. 
  
absolute path: A filename that begins with a slash (/) which equates to the
root directory. A filename specified in this way locates a file without
reference to the current working directory. 
  
Bourne Shell: The original shell program distributed with the UNIX
operating system (AT&T). Like the C-shell, it has a programming language
for writing scripts. It does not have the history mechanism, alias
mechanism, or job control features like the C-shell. 
  
command history: A facility where previously executed commands are kept in
a list. These commands can be recalled, modified, and executed from the
history list. The number of entries in the list is controlled by a number
stored in the shell variable $history. Another shell variable, $savehist,
controls how many history commands are saved at the end of the shell
session. Saved history is recalled automatically at the start of the next
shell session
  
command prompt: The string of characters displayed by the shell, in
interactive mode, to indicate that it is ready to accept another command
line. The contents and format of the command prompt can be customized by
the user by using the built-in shell variable $prompt. 
  
command substitution: An operation in which the results of a command
enclosed in backticks (') are substituted for the entire expression,
including the backticks. Using this method, you can use one command to
prepare arguments or parameters for another command. 
  
current working directory: The directory, represented by the special
filename dot (.) or hidden file name, where simple filenames are read from
or written to. Relative pathnames start from this directory. The contents
of the built-in shell variable $cwd always contain the current pathname of
this directory, and the UNIX command pwd always displays this pathname as
well. 
  
dot file: Any filename that begins with a period (.) is considered as one
of these. They are also referred as silent or hidden files on some
versionsof UNIX. Dot files are found most frequently in a user's home
directory, and typically they contain configuration or other pertinent
information for a UNIX command. (e.g. .cshrc file, .login, .profile
files). 
  
environmental variable: A shell variable that is created and initialized
with the setenv command. These variables are known as global variables.
The contents of an environment variable are accessible to the current
shell and any child processes that it creates. Environment variables are
one of the ways that one process can communicate information with another.
These variables are removed or deleted by the use of unsetenv command. 
  
event number: A unique number associated with each command, known as a
history event, kept in the C-shell history list. The event number can be
used to recall a specific command from history by prefixing it with an
exclamation mark (!) at a command prompt. 
  
exec(): The UNIX function call that locates a command file or script and
replaces the current process image with that command. It is used by the
shell and any other command to initiate a new command as a child process.
The exec() function does not close any files open at the ppoint that it is
called. The command that is initiated inherits these files opened and
positioned, as they were for the calling program. 
  
expansion/substitution metacharacters: A group of special characters that
act as special indicators to the C-shell (e.g. $, !, and : - used to
indicate the start of the shell variable, history events, and substitution
modifiers on a command-line). The shell acts accordingly to expand the
items prefixed with $ or !, and it modifies accordingly those suffixed
with : 
  
file redirection: The process of reading input for a command from a disk
file rather than from stdin, or writing the output of a command to a disk
file rather than to stdout. It also includes appending output to a disk
file. Although file output redirection normally writes stdout to a file,
it can also merge the output from stderr to the same destination as that
of stdout. There is also an option to override the protection offered by
setting the shell variable $noclobber. 
  
filename completion: A shell mechanism, optionally enabled by the existence
of the shell variable $filec . It completes the current filename on a
command-line, when the user presses the Escape key, up to th epoint where
the name is ambiguous. Typing ^D at any time in a search displays a list
of filenames that match the pattern entered, upto the current point. 
  
filename metacharacters: The special characters used on a command-line to
form filename match patterns. Also used to identify or form abbreviations. 
  
filter: A command that takes input from stdin and writes output to stdout
as its default.Filters are usually not interactive and do not issue
prompts for input. Instead, they read stdin continuously until they reach
EOF. 
  
foreground command: A command that is run from the command prompt and stays
attached to the terminal until it completes processing. You must wait for
the foreground command to finish before you receive a shell prompt and can
enter another command. 
  
fork():  The UNIX function call that creates a new child process that is an
exact duplicate of the process which called fork() . Any program,
including the C-shell, that initiates new commands or creates subprocesses
uses this function. The new child process will then usually call the
exec() function to actually start the command in its place in memory. 
  
globbing: The expansion process in which filename metacharacters are
replaced by a list of matching filenames. When globbing is inhibited with
the noglob command, filename metacharacters on a command-line are passed
to the command literally, rather than being replaced with a list of
matching filenames. 
  
here document: A type of input redirection indicated by the <<
metacharacters. A here document, used in a shell script, takes its input
from the script file lines that immediately follow the command-line.
Following the << characters on the command line is a word used to indicate
the end of the here document. 
  
history event: The commands in the C-shell history list are history events.
Each event has a number associated with it, which starts at 1 when the
session begins and is increased with each new command-line entered. 
  
home directory: The directory which is a user's initial working directory
after logging onto the system. It is the directory referenced by ~. When
the cd command is entered without an argument, it changes to the user's
home directory by default. 
  
input/output metacharacters: The special metacharacters that preface a
filename to be used for file redirection. These metacharacters permit a
command to take its input from a file rather than from stdin or write
output to a file rather than stdout. They can also be used to indicate a
merging output to stderr with output going to stdout. 
  
job control: The ability to manipulate processes that are either attached
to a terminl and running in foreground, or detached and running in
background. Job control permits the suspension of processes and also the
changing of process states to any of three states- foreground, background,
or suspended. The status of all jobs can also be reported. 
  
Korn shell: The latest shell prpogram distributed with many versions of
UNIX OS.This shell is 95% compatible with Bourne shell. It has many
features similar to C-shell, like the history, editing, aliases and job
control commands. 
  
local variable: A shell variable that is created and initialized with the
set command. These variables, unlike environment variables, are only
accessible by the shell process that created them. They cannot be passed
on to a child process. Local variables can be boolean or can contain
strings or numbers on which simple arithmetic operations can be performed. 
  
login shell: The initial shell process that is created after a user logs in
to the system. When running C-shell, the login shell reads the .login
startup file after reading .cshrc. Other C-shell processes started as
children of this shell only read the .cshrc file. When this shell
terminated, the user is logged out of the system. 
  
metacharacter: Any of the special characters which, in different contexts,
have special meaning or significance to the C-shell. These characters are
interpreted by the shell when the command-line is read and processed,
prior to being executed. 
  
multiword variable: A local variable that has multiple elements, one for
each word or quoted string in its assigned value. Multiword variables are
similar to array variables in many programming languages. Individual words
of the variable can be addressed directly by the use of subscripts
enclosed in square brackets. 
  
pager: One of several UNIX commands used to process long input and break it
up into screen pages. These commands cause a pause at the bottom of the
screen after each page is displayed and wait for user input before
proceeding. They have different capabilities as far as searching for text,
moving forward or backward through the text, and controlling how the
screen pages are displayed. The three most common paging commands are -
more, pg, and less. 
  
pathname: An optional string of directory names, separated by slashes and
followed by a filename, which locates where the filename resides. A
pathname is absolute if it starts with the root directory (/), or relative
if it starts from a working directory, or the special directories dor (.),
or dot dot (..)
  
pattern matching: The process of interpretting metacharacter patterns, and
replacing them with list of filenames or strings that match the patterns.
Pattern matching is used in command-lines to generate filename arguments
and in text-processing commands to match strings of text. 
  
pipeline: A connection of two or more commands, where the standard output
of one command is joined to the standard input of the next in the
pipe-line. The vertical bar (|) is used to indicate the creation of a
pipeline to the shell (e.g.: cat myfile.c | more). 
  
process: An instance of a command running in its own environment in memory.
Each process on the system is unique and is assigned an ID number, known
as PID. A process provides an environment, which is a copy of its parent's
environment, for the command that it is running. 
  
process ID: The unique number, known as PID, by which a specific process
can be referenced. The PID can be used to kill an abnormal or unwanted
process. Commands such as ps report PIDs for each running process on the
system. 
  
quotation metacharacters: The special characters used to create strings for
arguments to commands or for assignment to variables. These metacharacters
can control when other metacharacters, contained within the strings, are
expanded or protected from expansion by the shell. They can also singly
protect specific metacharacters from being interpreted by the shell. 
  
relative path: A filename that is qualified relative to the current working
directory. A relative path begins with a directory name or one fo the
special directory file dot (.) or dot dot (..), which denote the current
directory or its parent, respectively. 
  
search path: A list of directories that is used to search for a command
file. The order of the directories in this list controls the order in
which the search is performed. The C- shell creates a hash table
containing all of the executable files in each of the search path
directories, except for the dot (.), which is searched directly when
required. 
  
shell script: A text file containing a shell program comprised of command
lines and shell control structures. Shell scripts can be used as direct
input to a shell process or can be made executable and run by name. 
  
shell variable: A name and its associated value, which can be a characters
string, or a number, or a Boolean/logical value. Variables can be defined
by the user or can be a part of the set of built-in variables used to
affect the environment in which the shell operates. 
  
stderr: The file available to a program for error, diagnostic, or informal
mesages. The default destination for the output is the terminal, display
device or window associated with the program. 
  
stdin: The file from which a program can read input. The default source for
this fileis the keyboard, unless file redirection is used. 
  
subshell: When a shell explicitly starts another copy of itself, or when a
shell calls the fork() system function, the resulting child process that
is created is known as a subshell. In the case where fork() is called,
this subshell is usually also calls the exec() function to start a
specified command.
  
syntactic metacharacters: The special characters used as punctuation
characters between and around commands. They are also used to combine
multiple UNIX commands to make a single logical command. Syntactic
metacharacters provide a means to effect conditional execution of a
command or commands, based on the outcome of a previous command. 
  
wild-card characters: Filename metacharacters are often referred to as
wild-card

