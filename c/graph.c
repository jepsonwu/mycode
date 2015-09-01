#include <stdio.h>
#include <stdlib.h>

#define NUM_V 5

typedef struct node *position;
typedef int elementTP;

struct node{
	elementTP element;
	position next;
};

void print_graph(position graph,int num);
void insert_edge(position graph,int from,int to);

void main()
{
	struct node graph[NUM_V];
	int i;

	for (i = 0; i < NUM_V; ++i)
	{
		(graph+i)->element=i;
		(graph+i)->next=NULL;
	}

	insert_edge(graph,1,2);
	insert_edge(graph,1,4);
	insert_edge(graph,2,3);
	insert_edge(graph,4,5);
	insert_edge(graph,4,3);

	print_graph(graph,NUM_V);
}

/**
 * 插入图的边
 * @param graph [description]
 * @param from  [description]
 * @param to    [description]
 */
void insert_edge(position graph,int from,int to){
	position np=graph+from;
	position node=(position)malloc(sizeof(struct node));

	node->element=to;
	node->next=np->next;

	np->next=node;
}

/**
 * 打印图
 * @param graph [description]
 * @param num   [description]
 */
void print_graph(position graph,int num){
	int i;
	position np;
	elementTP element;

	for (i = 1; i < num; ++i)
	{
		np=graph+i;
		element=np->element;
		printf("From  %d:",element);

		while(np->next!=NULL){
			printf("%d---->%d,",element,np->next->element);
			np=np->next;
		}
		printf("\n");
	}
}