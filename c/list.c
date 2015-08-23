#include <stdio.h>
#include <stdlib.h>
typedef struct node *LIST;//结构指针
typedef struct node *position;//结构指针

struct node{
	int element;
	position next;
};

LIST init_list(void);
void print_list(LIST L);
void insert_node(position np,int value);
int is_null(LIST L);
position find_value(LIST L,int value);
void delete_node(LIST L,position np);
position find_last(LIST L);
void delete_list(LIST L);

//所有操作都是指针
void main()
{
	LIST L;
	L=init_list();
	print_list(L);

	int i;
	int arr[5]={1,3,5,6,7};

	for (i = 0; i < 5; ++i)
	{
		insert_node(L,arr[i]);
	}
	print_list(L);

	position np;
	np=find_value(L,5);
	delete_node(L,np);
	print_list(L);

	np=find_last(L);
	printf("%d\n",np->element);

	delete_list(L);
}

/**
 * 初始化链表
 * @return  [description]
 */
LIST init_list(void){
	LIST L;
	L=(position)malloc(sizeof(struct node));
	L->next=NULL;
	return L;
}

/**
 * 打印列表
 * @param L [description]
 */
void print_list(LIST L){
	if(is_null(L)){
		printf("Empty list\n");
		return;
	}

	position np;
	np=L;

	while(np->next!=NULL){
		np=np->next;
		printf("%p:%d\n", np,np->element);
	}
}

/**
 * 插入链表
 * @param np    [description]
 * @param value [description]
 */
void insert_node(position np,int value){
	position node;

	node=(position)malloc(sizeof(struct node));
	node->element=value;
	node->next=np->next;

	np->next=node;
}

/**
 * 查找节点
 * @param  L     [description]
 * @param  value [description]
 * @return       [description]
 */
position find_value(LIST L,int value){
	position np;

	np=L;
	while(np->next!=NULL){
		np=np->next;
		if(np->element==value) return np;
	}

	return NULL;
}

/**
 * 寻找最后的节点
 * @param  L [description]
 * @return   [description]
 */
position find_last(LIST L){
	position np;
	np=L;

	while(np->next!=NULL){
		np=np->next;
	}

	return np;
}

/**
 * 删除节点
 * @param L  [description]
 * @param np [description]
 */
void delete_node(LIST L,position np){
	position prev;

	prev=L;
	while(prev->next!=NULL){
		if(prev->next!=np)
			prev=prev->next;
		else
			break;
	}

	prev->next=np->next;
	free(np);
}

void delete_list(LIST L){
	position next;

	do{
		next=L->next;
		free(L);
		L=next;
	}while(next!=NULL);
}
/**
 * 判断链表是否为空
 * @param  L [description]
 * @return   [description]
 */
int is_null(LIST L){
	return ((L->next)==NULL);
}